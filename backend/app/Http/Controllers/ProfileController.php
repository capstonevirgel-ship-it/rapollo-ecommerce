<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile.
     */
    public function show()
    {
        $user = Auth::user();
        
        // Get or create profile for the user
        $profile = Profile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'full_name' => null,
                'phone' => null,
                'address' => null,
                'city' => null,
                'postal_code' => null,
                'country' => 'Philippines',
                'avatar_url' => null,
            ]
        );
        
        return response()->json($profile);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        // Validate the request
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'avatar_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get or create profile
        $profile = Profile::firstOrCreate(
            ['user_id' => $user->id]
        );

        // Update profile
        $profile->update($request->only([
            'full_name',
            'phone',
            'address',
            'city',
            'postal_code',
            'country',
            'avatar_url'
        ]));

        return response()->json($profile);
    }

    /**
     * Upload avatar image.
     */
    public function uploadAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $profile = Profile::firstOrCreate(
            ['user_id' => $user->id]
        );

        // Delete old avatar if exists
        if ($profile->avatar_url && Storage::disk('public')->exists($profile->avatar_url)) {
            Storage::disk('public')->delete($profile->avatar_url);
        }

        // Store new avatar using Storage facade
        $path = $request->file('avatar')->store('avatars', 'public');

        // Update profile with new avatar URL
        $profile->avatar_url = $path;
        $profile->save();

        return response()->json([
            'message' => 'Avatar uploaded successfully',
            'avatar_url' => Storage::url($path)
        ]);
    }
}
