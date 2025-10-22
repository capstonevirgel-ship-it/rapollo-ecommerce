<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_name' => ['required', 'string', 'min:8', 'max:255', 'unique:'.User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', 'max:128', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'user_name' => $request->user_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user', // Default role for new users
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Check if this is an API request
        if ($request->expectsJson() || $request->is('api/*')) {
            // For API requests, create a Sanctum token
            $token = $user->createToken('api-token')->plainTextToken;
            
            return response()->json([
                'message' => 'Registration successful',
                'user' => $user,
                'token' => $token
            ]);
        }

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user
        ]);
    }
}
