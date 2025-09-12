<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

            return response()->json([
                'message' => 'Tickets booked successfully',
                'tickets' => $tickets,
                'total_price' => $totalPrice
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
}
