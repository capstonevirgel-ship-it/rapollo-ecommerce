<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\User;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Payment;
use App\Services\PayMongoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets for the authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tickets = Ticket::with(['event', 'user'])
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
        $tickets = Ticket::with(['event', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

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

            // Create purchase record for tickets
            $purchase = new Purchase();
            $purchase->user_id = $user->id;
            $purchase->total = $totalPrice;
            $purchase->status = 'pending';
            $purchase->type = 'ticket';
            $purchase->event_id = $event->id;
            $purchase->save();

            // Create purchase item
            $purchaseItem = new PurchaseItem();
            $purchaseItem->purchase_id = $purchase->id;
            $purchaseItem->quantity = $quantity;
            $purchaseItem->price = $event->ticket_price;
            $purchaseItem->save();

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

        $ticket = Ticket::findOrFail($id);
        $ticket->status = $request->status;
        $ticket->save();

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

        if ($ticket->status === 'used') {
            return response()->json(['error' => 'Cannot cancel a used ticket'], 400);
        }

        $ticket->status = 'cancelled';
        $ticket->save();

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

            // Create purchase record for tickets
            $purchase = new Purchase();
            $purchase->user_id = $user->id;
            $purchase->total = $totalPrice;
            $purchase->status = 'pending';
            $purchase->type = 'ticket';
            $purchase->event_id = $event->id;
            $purchase->save();

            // Create purchase item
            $purchaseItem = new PurchaseItem();
            $purchaseItem->purchase_id = $purchase->id;
            $purchaseItem->quantity = $quantity;
            $purchaseItem->price = $event->ticket_price;
            $purchaseItem->save();

            // Create PayMongo checkout session
            $payMongoService = new PayMongoService();
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            $successUrl = $frontendUrl . '/payment/success';
            $cancelUrl = $frontendUrl . '/events';
            
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
                $cancelUrl
            );

            if (!$paymentResponse || !isset($paymentResponse['data']['attributes']['checkout_url'])) {
                throw new \Exception('Failed to create checkout session');
            }

            // Create payment record
            $payment = new Payment();
            $payment->user_id = $user->id;
            $payment->purchase_id = $purchase->id;
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
            'purchase_id' => 'required|integer|exists:purchases,id'
        ]);

        $purchase = Purchase::findOrFail($request->purchase_id);
        
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
                $existingTicketsCount = Ticket::where('purchase_id', $purchase->id)->count();
                
                if ($existingTicketsCount === 0) {
                    // Get purchase item to get quantity
                    $purchaseItem = $purchase->purchaseItems()->first();
                    $quantity = $purchaseItem ? $purchaseItem->quantity : 1;

                    // Create tickets
                    $tickets = [];
                    for ($i = 0; $i < $quantity; $i++) {
                        $ticket = new Ticket();
                        $ticket->event_id = $purchase->event_id;
                        $ticket->user_id = $purchase->user_id;
                        $ticket->purchase_id = $purchase->id;
                        $ticket->ticket_number = $ticket->generateTicketNumber();
                        $ticket->price = $purchase->event->ticket_price;
                        $ticket->status = 'confirmed';
                        $ticket->qr_code = $ticket->generateQRCode();
                        $ticket->booked_at = now();
                        $ticket->save();

                        $tickets[] = $ticket->load('event');
                    }
                } else {
                    // Tickets already exist, just load them
                    $tickets = Ticket::where('purchase_id', $purchase->id)->with('event')->get();
                }

                // Update purchase status
                $purchase->status = 'completed';
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
