<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        // Check if this is an API request
        if ($request->expectsJson() || $request->is('api/*')) {
            // For API requests, create a Sanctum token
            $user = $request->user();
            $token = $user->createToken('api-token')->plainTextToken;
            
            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token
            ]);
        }

        // For web requests, use session-based authentication
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful',
            'user' => $request->user()
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        // Check if this is an API request
        if ($request->expectsJson() || $request->is('api/*')) {
            // For API requests, revoke the current token
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'message' => 'Logout successful'
            ]);
        }

        // For web requests, use session-based logout
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }
}
