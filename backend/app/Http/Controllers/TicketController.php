<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\User;
use App\Models\TicketPurchase;
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
            $tickets = [];
            $totalPrice = $event->ticket_price * $quantity;

            for ($i = 0; $i < $quantity; $i++) {
                $ticket = new Ticket();
                $ticket->event_id = $event->id;
                $ticket->user_id = $user->id;
                $ticket->ticket_number = $ticket->generateTicketNumber();
                $ticket->price = $event->ticket_price;
                $ticket->status = 'confirmed';
                $ticket->qr_code = $ticket->generateQRCode();
                $ticket->booked_at = now();
                $ticket->save();

                $tickets[] = $ticket->load('event');
            }

            DB::commit();

            // Refresh the event to get updated ticket counts
            $event->refresh();
            $event->append(['booked_tickets_count', 'remaining_tickets']);

            return response()->json([
                'message' => 'Tickets booked successfully',
                'tickets' => $tickets,
                'total_price' => $totalPrice,
                'event' => $event
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to book tickets'], 500);
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
     * Create payment intent for ticket purchase
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

            // Create ticket purchase record
            $ticketPurchase = new TicketPurchase();
            $ticketPurchase->user_id = $user->id;
            $ticketPurchase->event_id = $event->id;
            $ticketPurchase->quantity = $quantity;
            $ticketPurchase->total_amount = $totalPrice;
            $ticketPurchase->status = 'pending';
            $ticketPurchase->save();

            // Create PayMongo payment intent
            $payMongoService = new PayMongoService();
            $paymentResponse = $payMongoService->createPaymentIntent(
                $totalPrice,
                'PHP',
                [
                    'ticket_purchase_id' => $ticketPurchase->id,
                    'event_id' => $event->id,
                    'user_id' => $user->id,
                    'quantity' => $quantity
                ]
            );

            // Update ticket purchase with payment details
            $ticketPurchase->payment_intent_id = $paymentResponse['data']['id'];
            $ticketPurchase->save();

            DB::commit();

            return response()->json([
                'message' => 'Payment intent created successfully',
                'checkout_url' => $paymentResponse['data']['attributes']['checkout_url'],
                'payment_intent_id' => $paymentResponse['data']['id'],
                'ticket_purchase_id' => $ticketPurchase->id,
                'total_amount' => $totalPrice
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to create ticket payment intent', [
                'error' => $e->getMessage(),
                'event_id' => $event->id,
                'user_id' => $user->id,
                'quantity' => $quantity
            ]);
            return response()->json(['error' => 'Failed to create payment intent'], 500);
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
            'ticket_purchase_id' => 'required|integer|exists:ticket_purchases,id'
        ]);

        $ticketPurchase = TicketPurchase::findOrFail($request->ticket_purchase_id);
        
        // Check if user owns this purchase
        if ($ticketPurchase->user_id !== Auth::id()) {
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
                // Create tickets
                $tickets = [];
                for ($i = 0; $i < $ticketPurchase->quantity; $i++) {
                    $ticket = new Ticket();
                    $ticket->event_id = $ticketPurchase->event_id;
                    $ticket->user_id = $ticketPurchase->user_id;
                    $ticket->ticket_purchase_id = $ticketPurchase->id;
                    $ticket->ticket_number = $ticket->generateTicketNumber();
                    $ticket->price = $ticketPurchase->event->ticket_price;
                    $ticket->status = 'confirmed';
                    $ticket->qr_code = $ticket->generateQRCode();
                    $ticket->booked_at = now();
                    $ticket->save();

                    $tickets[] = $ticket->load('event');
                }

                // Update ticket purchase status
                $ticketPurchase->status = 'completed';
                $ticketPurchase->payment_date = now();
                $ticketPurchase->save();

                // Refresh event to get updated ticket counts
                $event = $ticketPurchase->event;
                $event->refresh();
                $event->append(['booked_tickets_count', 'remaining_tickets']);

                DB::commit();

                return response()->json([
                    'message' => 'Payment confirmed and tickets created successfully',
                    'tickets' => $tickets,
                    'ticket_purchase' => $ticketPurchase,
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
                'ticket_purchase_id' => $ticketPurchase->id,
                'payment_intent_id' => $request->payment_intent_id
            ]);
            return response()->json(['error' => 'Failed to confirm payment'], 500);
        }
    }
}
