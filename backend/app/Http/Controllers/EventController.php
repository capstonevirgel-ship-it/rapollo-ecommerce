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

        // Append the computed attributes to ensure they're included in the response
        $events->getCollection()->transform(function ($event) {
            $event->append(['booked_tickets_count', 'remaining_tickets']);
            return $event;
        });

        return response()->json($events);
    }

    /**
     * Display the specified event
     */
    public function show($id)
    {
        $event = Event::with(['admin', 'tickets', 'comments.user'])
            ->findOrFail($id);

        // Append the computed attributes
        $event->append(['booked_tickets_count', 'remaining_tickets']);

        return response()->json($event);
    }

    /**
     * Store a newly created event (admin only)
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'content' => 'nullable|string',
            'date' => 'required|date|after:now',
            'location' => 'nullable|string|max:100',
            'poster_url' => 'nullable|string|max:255',
            'poster_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif,svg|max:5120',
            'ticket_price' => 'nullable|numeric|min:0',
            'max_tickets' => 'nullable|integer|min:1'
        ]);

        $event = new Event();
        $event->admin_id = Auth::id();
        $event->title = $request->title;
        $event->content = $request->content;
        $event->date = $request->date;
        $event->location = $request->location;
        $event->base_ticket_price = $request->base_ticket_price;
        $event->max_tickets = $request->max_tickets;
        $event->available_tickets = $request->max_tickets;

        // Handle poster image upload
        if ($request->hasFile('poster_image')) {
            $path = $request->file('poster_image')->store('events', 'public');
            $event->poster_url = $path;
        } else {
            $event->poster_url = $request->poster_url;
        }

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
            'content' => 'nullable|string',
            'date' => 'sometimes|required|date|after:now',
            'location' => 'nullable|string|max:100',
            'poster_url' => 'nullable|string|max:255',
            'poster_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif,svg|max:5120',
            'base_ticket_price' => 'nullable|numeric|min:0',
            'max_tickets' => 'nullable|integer|min:1'
        ]);

        // Handle poster image upload
        if ($request->hasFile('poster_image')) {
            // Delete old poster if exists
            if ($event->poster_url && \Storage::disk('public')->exists($event->poster_url)) {
                \Storage::disk('public')->delete($event->poster_url);
            }
            
            $path = $request->file('poster_image')->store('events', 'public');
            $event->poster_url = $path;
        } elseif ($request->has('poster_url')) {
            $event->poster_url = $request->poster_url;
        }

        // Update event fields - handle FormData correctly
        // Use all() to get all form data, then check each field
        $data = $request->all();
        
        if (isset($data['title'])) {
            $event->title = $data['title'];
        }
        
        // Content can contain HTML - preserve it as-is
        if (array_key_exists('content', $data)) {
            $event->content = $data['content'] ?? null;
        }
        
        if (isset($data['date'])) {
            $event->date = $data['date'];
        }
        
        if (isset($data['location'])) {
            $event->location = $data['location'];
        }
        
        // Handle base_ticket_price - check if key exists in data array
        if (array_key_exists('base_ticket_price', $data)) {
            $value = $data['base_ticket_price'];
            $event->base_ticket_price = ($value === '' || $value === null) ? null : $value;
        }
        
        // Handle max_tickets
        if (array_key_exists('max_tickets', $data)) {
            $value = $data['max_tickets'];
            $event->max_tickets = ($value === '' || $value === null) ? null : (int)$value;
        }
        
        $event->save();

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
