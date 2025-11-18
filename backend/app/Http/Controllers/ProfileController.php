<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile.
     */
    public function show()
    {
        $user = Auth::user();
        
        // Get or create profile for the user (new schema)
        $profile = Profile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'full_name' => null,
                'phone' => null,
                'street' => null,
                'barangay' => null,
                'city' => null,
                'province' => null,
                'zipcode' => null,
                'complete_address' => null,
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
            'street' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:150',
            'zipcode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'avatar_url' => 'nullable|string|max:255',
            'user_name' => [
                'required',
                'string',
                'min:8',
                'max:100',
                Rule::unique('users', 'user_name')->ignore($user->id),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
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

        // Derive safe street value ('n/a' when empty)
        $street = trim((string) $request->input('street'));
        if ($street === '') {
            $street = 'n/a';
        }

        // Compute complete address from provided parts
        $parts = array_filter([
            $street,
            $request->input('barangay'),
            $request->input('city'),
            $request->input('province'),
            $request->input('zipcode') ?: $request->input('postal_code')
        ], function ($v) {
            return $v !== null && trim((string) $v) !== '';
        });

        $completeAddress = count($parts) ? implode(', ', $parts) : null;

        // Update profile
        $profile->update(array_merge($request->only([
            'full_name',
            'phone',
            'city',
            'province',
            'barangay',
            'zipcode',
            'country',
            'avatar_url'
        ]), [
            'street' => $street,
            'complete_address' => $completeAddress,
        ]));

        // Update core user fields
        $userDirty = false;

        $newUserName = trim((string) $request->input('user_name'));
        if ($newUserName !== '' && $newUserName !== $user->user_name) {
            $user->user_name = $newUserName;
            $userDirty = true;
        }

        $newEmail = trim((string) $request->input('email'));
        if ($newEmail !== '' && $newEmail !== $user->email) {
            $user->email = $newEmail;
            $userDirty = true;
        }

        if ($userDirty) {
            $user->save();
        }

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
            'avatar_url' => Storage::url($path),
            'avatar_path' => $path,
        ]);
    }
}
