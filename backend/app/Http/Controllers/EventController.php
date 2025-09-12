<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    /**
     * Display a listing of events
     */
    public function index(Request $request)
    {
        $events = Event::with(['admin', 'tickets'])
            ->orderBy('date', 'asc')
            ->paginate(12);

        return response()->json($events);
    }

    /**
     * Display the specified event
     */
    public function show($id)
    {
        $event = Event::with(['admin', 'tickets', 'comments.user'])
            ->findOrFail($id);

        return response()->json($event);
    }

    /**
     * Store a newly created event (admin only)
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string',
            'date' => 'required|date|after:today',
            'location' => 'nullable|string|max:100',
            'poster_url' => 'nullable|string|max:255',
            'ticket_price' => 'nullable|numeric|min:0',
            'max_tickets' => 'nullable|integer|min:1'
        ]);

        $event = new Event();
        $event->admin_id = Auth::id();
        $event->title = $request->title;
        $event->description = $request->description;
        $event->date = $request->date;
        $event->location = $request->location;
        $event->poster_url = $request->poster_url;
        $event->ticket_price = $request->ticket_price;
        $event->max_tickets = $request->max_tickets;
        $event->available_tickets = $request->max_tickets;
        $event->save();

        return response()->json([
            'message' => 'Event created successfully',
            'event' => $event->load('admin')
        ], 201);
    }

    /**
     * Update the specified event (admin only)
     */
    public function update(Request $request, $id)
    {
        $event = Event::where('admin_id', Auth::id())->findOrFail($id);

        $request->validate([
            'title' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'date' => 'sometimes|required|date|after:today',
            'location' => 'nullable|string|max:100',
            'poster_url' => 'nullable|string|max:255',
            'ticket_price' => 'nullable|numeric|min:0',
            'max_tickets' => 'nullable|integer|min:1'
        ]);

        $event->update($request->only([
            'title', 'description', 'date', 'location', 
            'poster_url', 'ticket_price', 'max_tickets'
        ]));

        return response()->json([
            'message' => 'Event updated successfully',
            'event' => $event->load('admin')
        ]);
    }

    /**
     * Remove the specified event (admin only)
     */
    public function destroy($id)
    {
        $event = Event::where('admin_id', Auth::id())->findOrFail($id);
        $event->delete();

        return response()->json(['message' => 'Event deleted successfully']);
    }
}
