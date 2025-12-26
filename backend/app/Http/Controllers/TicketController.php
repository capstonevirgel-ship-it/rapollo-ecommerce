<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\User;
use App\Models\TicketPurchase;
use App\Models\Payment;
use App\Services\PayMongoService;
use App\Services\NotificationService;
use App\Mail\TicketCancelled;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets for the authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tickets = Ticket::with(['event', 'user', 'ticketPurchase'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($tickets);
    }

    /**
     * Display all tickets for admin
     */
    public function adminIndex(Request $request)
    {
        $query = Ticket::with(['event', 'user']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('user_name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('event', function ($eventQuery) use ($search) {
                      $eventQuery->where('title', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $request->get('per_page', 20);
        $tickets = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($tickets);
    }

    /**
     * Display tickets for a specific event (admin only)
     */
    public function eventTickets(Request $request, $eventId)
    {
        $tickets = Ticket::with(['user'])
            ->where('event_id', $eventId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tickets);
    }

    /**
     * Book a ticket for an event
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Prevent admins from purchasing tickets
        if ($user->role === 'admin') {
            return response()->json([
                'error' => 'Administrators cannot purchase tickets. Please use a customer account to buy tickets.'
            ], 403);
        }

        // Check if user is suspended
        if ($user->isSuspended()) {
            return response()->json([
                'error' => 'Your account has been suspended. You cannot purchase tickets. Please contact support if you believe this is an error.',
                'message' => 'account_suspended',
                'suspension_reason' => $user->suspension_reason,
            ], 403);
        }

        $request->validate([
            'event_id' => 'required|exists:events,id',
            'quantity' => 'required|integer|min:1|max:5'
        ]);

        $event = Event::findOrFail($request->event_id);
        $user = Auth::user();
        $quantity = $request->quantity;

        // Check if event has ticket sales enabled
        if (!$event->ticket_price || !$event->max_tickets) {
            return response()->json(['error' => 'This event does not have ticket sales enabled'], 400);
        }

        // Check if user has already booked tickets for this event
        $existingTickets = $user->tickets()->where('event_id', $event->id)->whereIn('status', ['pending', 'confirmed'])->count();
        if ($existingTickets + $quantity > 5) {
            return response()->json(['error' => 'You can only book a maximum of 5 tickets per event'], 400);
        }

        // Check if enough tickets are available
        if ($event->isFullyBooked() || $event->remaining_tickets < $quantity) {
            return response()->json(['error' => 'Not enough tickets available'], 400);
        }

        DB::beginTransaction();
        try {
            $totalPrice = $event->ticket_price * $quantity;

            // Create ticket purchase record
            $purchase = TicketPurchase::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'total' => $totalPrice,
            ]);

            // NOTE: Tickets will be created after successful payment via webhook
            // This prevents duplicate ticket creation and ensures payment is confirmed first

            DB::commit();

            // Refresh the event to get updated ticket counts
            $event->refresh();
            $event->append(['booked_tickets_count', 'remaining_tickets']);

            return response()->json([
                'message' => 'Purchase created successfully. Complete payment to confirm tickets.',
                'purchase' => $purchase,
                'total_price' => $totalPrice,
                'event' => $event,
                'quantity' => $quantity
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to create purchase'], 500);
        }
    }

    /**
     * Display the specified ticket
     */
    public function show($id)
    {
        $ticket = Ticket::with(['event', 'user'])->findOrFail($id);
        
        // Check if user owns the ticket or is admin
        if (Auth::user()->id !== $ticket->user_id && Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($ticket);
    }

    /**
     * Update ticket status (admin only)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,used'
        ]);

        $ticket = Ticket::with(['event', 'user'])->findOrFail($id);
        $oldStatus = $ticket->status;
        $ticket->status = $request->status;
        $ticket->save();

        // If admin cancelled the ticket, send notification to user (no email, no admin notification)
        if ($oldStatus !== 'cancelled' && $request->status === 'cancelled') {
            $user = $ticket->user;
            if ($user) {
                // Send in-app notification to user only
                NotificationService::createEventNotification(
                    $user,
                    'Ticket Cancelled',
                    'Your ticket ' . $ticket->ticket_number . ' for "' . ($ticket->event->title ?? 'the event') . '" has been cancelled by an administrator.',
                    [
                        'action_url' => '/my-tickets',
                        'action_text' => 'View Tickets'
                    ]
                );
            }

            // Check for auto-suspension after cancellation
            if ($user && $user->role === 'user') {
                \App\Services\UserSuspensionService::checkAndSuspendIfNeeded($user);
            }
        }

            // Broadcast count update to all admins via WebSocket
            $pendingTicketsCount = Ticket::where('status', 'pending')->count();
            \App\Services\NotificationService::broadcastCountUpdate('pending_tickets', $pendingTicketsCount);

            return response()->json([
                'message' => 'Ticket status updated successfully',
            'ticket' => $ticket->load(['event', 'user'])
        ]);
    }

    /**
     * Cancel a ticket (user only)
     */
    public function cancel($id)
    {
        $ticket = Ticket::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Only allow cancellation when status is 'confirmed'
        if ($ticket->status !== 'confirmed') {
            return response()->json([
                'error' => 'Only confirmed tickets can be cancelled',
                'message' => 'This ticket cannot be cancelled because its status is not confirmed.'
            ], 400);
        }

        $oldStatus = $ticket->status;
        $ticket->status = 'cancelled';
        $ticket->save();

        // Load relationships for notifications
        $ticket->load(['event', 'user']);

        // Check for auto-suspension after cancellation
        $user = $ticket->user;
        if ($user && $user->role === 'user') {
            \App\Services\UserSuspensionService::checkAndSuspendIfNeeded($user);
        }

        // Send email and in-app notification to user
        if ($oldStatus !== 'cancelled' && $user) {
            try {
                // Send email notification to user
                Mail::to($user->email)->send(new TicketCancelled($ticket, $user));
            } catch (\Exception $e) {
                Log::error('Failed to send ticket cancellation email', [
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Send in-app notification to user
            NotificationService::createEventNotification(
                $user,
                'Ticket Cancelled',
                'Your ticket ' . $ticket->ticket_number . ' for "' . ($ticket->event->title ?? 'the event') . '" has been cancelled.',
                [
                    'action_url' => '/my-tickets',
                    'action_text' => 'View Tickets'
                ]
            );

            // Send in-app notification to all admins (no email)
            $adminUsers = User::where('role', 'admin')->get();
            foreach ($adminUsers as $admin) {
                NotificationService::createEventNotification(
                    $admin,
                    'Ticket Cancelled by Customer',
                    $user->user_name . ' has cancelled their ticket ' . $ticket->ticket_number . ' for "' . ($ticket->event->title ?? 'an event') . '".',
                    [
                        'action_url' => '/admin/tickets',
                        'action_text' => 'View Tickets'
                    ]
                );
            }
        }

        return response()->json([
            'message' => 'Ticket cancelled successfully',
            'ticket' => $ticket->load('event')
        ]);
    }

    /**
     * Get ticket statistics for admin
     */
    public function statistics()
    {
        $stats = [
            'total_tickets' => Ticket::count(),
            'confirmed_tickets' => Ticket::where('status', 'confirmed')->count(),
            'pending_tickets' => Ticket::where('status', 'pending')->count(),
            'cancelled_tickets' => Ticket::where('status', 'cancelled')->count(),
            'used_tickets' => Ticket::where('status', 'used')->count(),
            'total_revenue' => (float) (Ticket::where('status', 'confirmed')->sum('price') ?? 0),
            'recent_bookings' => Ticket::with(['event', 'user'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
        ];

        return response()->json($stats);
    }

    /**
     * Create checkout session for ticket purchase
     */
    public function createPaymentIntent(Request $request)
    {
        $user = Auth::user();
        
        // Prevent admins from purchasing tickets
        if ($user->role === 'admin') {
            return response()->json([
                'error' => 'Administrators cannot purchase tickets. Please use a customer account to buy tickets.'
            ], 403);
        }

        // Check if user is suspended
        if ($user->isSuspended()) {
            return response()->json([
                'error' => 'Your account has been suspended. You cannot purchase tickets. Please contact support if you believe this is an error.',
                'message' => 'account_suspended',
                'suspension_reason' => $user->suspension_reason,
            ], 403);
        }

        $request->validate([
            'event_id' => 'required|exists:events,id',
            'quantity' => 'required|integer|min:1|max:5'
        ]);

        $event = Event::findOrFail($request->event_id);
        $user = Auth::user();
        $quantity = $request->quantity;

        // Check if event has ticket sales enabled
        if (!$event->ticket_price || !$event->max_tickets) {
            return response()->json(['error' => 'This event does not have ticket sales enabled'], 400);
        }

        // Check if user has already booked tickets for this event
        $existingTickets = $user->tickets()->where('event_id', $event->id)->whereIn('status', ['pending', 'confirmed'])->count();
        if ($existingTickets + $quantity > 5) {
            return response()->json(['error' => 'You can only book a maximum of 5 tickets per event'], 400);
        }

        // Check if enough tickets are available
        if ($event->isFullyBooked() || $event->remaining_tickets < $quantity) {
            return response()->json(['error' => 'Not enough tickets available'], 400);
        }

        DB::beginTransaction();
        try {
            $totalPrice = $event->ticket_price * $quantity;

            // Create ticket purchase record
            $purchase = TicketPurchase::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'total' => $totalPrice
            ]);

            // Create PayMongo checkout session
            $payMongoService = new PayMongoService();
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            $successUrl = $frontendUrl . '/checkout/success';
            
            $paymentResponse = $payMongoService->createCheckoutSession(
                $totalPrice,
                'PHP',
                [
                    'purchase_id' => $purchase->id,
                    'event_id' => $event->id,
                    'user_id' => $user->id,
                    'quantity' => $quantity
                ],
                $successUrl,
                null
            );

            if (!$paymentResponse || !isset($paymentResponse['data']['attributes']['checkout_url'])) {
                throw new \Exception('Failed to create checkout session');
            }

            // Create payment record
            $payment = new Payment();
            $payment->user_id = $user->id;
            $payment->purchasable_type = TicketPurchase::class;
            $payment->purchasable_id = $purchase->id;
            $payment->amount = $totalPrice;
            $payment->currency = 'PHP';
            $payment->status = 'pending';
            $payment->payment_method = 'paymongo';
            $payment->transaction_id = $paymentResponse['data']['id'];
            $payment->payment_date = null;
            $payment->notes = 'PayMongo checkout session created for ticket purchase';
            $payment->metadata = json_encode($paymentResponse);
            $payment->save();

            DB::commit();

            return response()->json([
                'message' => 'Checkout session created successfully',
                'checkout_url' => $paymentResponse['data']['attributes']['checkout_url'],
                'checkout_session_id' => $paymentResponse['data']['id'],
                'purchase_id' => $purchase->id,
                'total_amount' => $totalPrice
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to create ticket checkout session', [
                'error' => $e->getMessage(),
                'event_id' => $event->id,
                'user_id' => $user->id,
                'quantity' => $quantity
            ]);
            return response()->json(['error' => 'Failed to create checkout session'], 500);
        }
    }

    /**
     * Confirm ticket payment
     */
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
            'payment_method_id' => 'required|string',
            'purchase_id' => 'required|integer|exists:ticket_purchases,id'
        ]);

        $purchase = TicketPurchase::findOrFail($request->purchase_id);
        
        // Check if user owns this purchase
        if ($purchase->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        try {
            // Confirm payment with PayMongo
            $payMongoService = new PayMongoService();
            $paymentResponse = $payMongoService->confirmPayment(
                $request->payment_intent_id,
                $request->payment_method_id
            );

            if ($paymentResponse['data']['attributes']['status'] === 'succeeded') {
                // Check if tickets already exist to avoid duplicates
                $existingTicketsCount = Ticket::where('ticket_purchase_id', $purchase->id)->count();
                
                // Get quantity from request metadata or default to 1
                $quantity = $request->input('quantity', 1);
                
                if ($existingTicketsCount === 0) {
                    // Create tickets
                    $tickets = [];
                    for ($i = 0; $i < $quantity; $i++) {
                        $ticket = new Ticket();
                        $ticket->event_id = $purchase->event_id;
                        $ticket->user_id = $purchase->user_id;
                        $ticket->ticket_purchase_id = $purchase->id;
                        $ticket->ticket_number = $ticket->generateTicketNumber();
                        $ticket->price = $purchase->event->ticket_price;
                        $ticket->status = 'confirmed';
                        $ticket->booked_at = now();
                        $ticket->save();

                        $tickets[] = $ticket->load('event');
                    }
                } else {
                    // Tickets already exist, just load them
                    $tickets = Ticket::where('ticket_purchase_id', $purchase->id)->with('event')->get();
                }

                // Update purchase paid_at
                $purchase->paid_at = now();
                $purchase->save();

                // Refresh event to get updated ticket counts
                $event = $purchase->event;
                $event->refresh();
                $event->append(['booked_tickets_count', 'remaining_tickets']);

                DB::commit();

                return response()->json([
                    'message' => 'Payment confirmed and tickets created successfully',
                    'tickets' => $tickets,
                    'purchase' => $purchase,
                    'event' => $event
                ]);
            } else {
                DB::rollback();
                return response()->json(['error' => 'Payment failed'], 400);
            }

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to confirm ticket payment', [
                'error' => $e->getMessage(),
                'purchase_id' => $purchase->id,
                'payment_intent_id' => $request->payment_intent_id
            ]);
            return response()->json(['error' => 'Failed to confirm payment'], 500);
        }
    }
}
