<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WebSocketController extends Controller
{
    /**
     * Validate token for WebSocket connection
     * This endpoint is called by the WebSocket server to authenticate users
     */
    public function validateToken(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'user_name' => $user->user_name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }
}

