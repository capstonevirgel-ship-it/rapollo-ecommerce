<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Upload an image file
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,webp,gif,svg|max:5120', // 5MB max
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            
            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            
            // Store the file
            $path = $file->storeAs('uploads', $filename, 'public');
            
            // Return the URL
            $url = Storage::disk('public')->url($path);
            
            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path,
                'filename' => $filename
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No image file provided'
        ], 400);
    }

    /**
     * Upload multiple images
     */
    public function uploadImages(Request $request)
    {
        $request->validate([
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'file|mimes:jpeg,png,jpg,webp,gif,svg|max:5120',
        ]);

        $uploadedImages = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                // Generate unique filename
                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid() . '.' . $extension;
                
                // Store the file
                $path = $file->storeAs('uploads', $filename, 'public');
                
                // Get the URL
                $url = Storage::disk('public')->url($path);
                
                $uploadedImages[] = [
                    'url' => $url,
                    'path' => $path,
                    'filename' => $filename
                ];
            }
        }

        return response()->json([
            'success' => true,
            'images' => $uploadedImages
        ]);
    }

    /**
     * Upload event content image (for rich text editor)
     */
    public function uploadEventContentImage(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,webp,gif,svg|max:5120', // 5MB max
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            
            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            
            // Store the file in events/content/ directory
            $path = $file->storeAs('events/content', $filename, 'public');
            
            // Return the URL - use Storage URL which respects filesystem config
            $url = Storage::disk('public')->url($path);
            
            // Ensure URL includes port for localhost in development
            if (strpos($url, 'http://localhost/storage') === 0) {
                $url = str_replace('http://localhost/storage', 'http://localhost:8000/storage', $url);
            }
            
            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path,
                'filename' => $filename
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No image file provided'
        ], 400);
    }
}
