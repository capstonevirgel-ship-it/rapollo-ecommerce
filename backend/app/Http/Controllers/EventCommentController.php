<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EventComment;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EventCommentController extends Controller
{
    /**
     * Get comments for a specific event
     */
    public function index(Request $request, $eventId)
    {
        $event = Event::findOrFail($eventId);
        
        $comments = EventComment::with([
            'user:id,user_name,email',
            'user.profile:id,user_id,avatar_url'
        ])
            ->where('event_id', $eventId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($comments);
    }

    /**
     * Store a new comment
     */
    public function store(Request $request, $eventId)
    {
        // Prevent admins from commenting
        if (Auth::user()->role === 'admin') {
            return response()->json([
                'message' => 'Administrators cannot comment on events. Please use a customer account to post comments.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:1000|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $event = Event::findOrFail($eventId);

        $comment = EventComment::create([
            'event_id' => $eventId,
            'user_id' => Auth::id(),
            'comment' => $request->comment
        ]);

        $comment->load(['user:id,user_name,email', 'user.profile:id,user_id,avatar_url']);

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment
        ], 201);
    }

    /**
     * Update a comment (only by the author)
     */
    public function update(Request $request, $eventId, $commentId)
    {
        // Prevent admins from updating comments
        if (Auth::user()->role === 'admin') {
            return response()->json([
                'message' => 'Administrators cannot update comments.'
            ], 403);
        }

        $comment = EventComment::where('event_id', $eventId)
            ->where('user_id', Auth::id())
            ->findOrFail($commentId);

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:1000|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $comment->update([
            'comment' => $request->comment
        ]);

        $comment->load(['user:id,user_name,email', 'user.profile:id,user_id,avatar_url']);

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => $comment
        ]);
    }

    /**
     * Delete a comment (only by the author)
     */
    public function destroy($eventId, $commentId)
    {
        // Prevent admins from deleting comments
        if (Auth::user()->role === 'admin') {
            return response()->json([
                'message' => 'Administrators cannot delete comments.'
            ], 403);
        }

        $comment = EventComment::where('event_id', $eventId)
            ->where('user_id', Auth::id())
            ->findOrFail($commentId);

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully'
        ]);
    }
}
