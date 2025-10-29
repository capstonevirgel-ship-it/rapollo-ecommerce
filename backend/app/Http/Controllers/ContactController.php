<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;

class ContactController extends Controller
{
    /**
     * Handle contact form submission
     */
    public function submit(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|in:general,order,return,technical,partnership,other',
            'message' => 'required|string|max:5000',
        ]);

        try {
            // Prepare form data
            $formData = [
                'firstName' => $validated['firstName'],
                'lastName' => $validated['lastName'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? '',
                'subject' => $validated['subject'],
                'message' => $validated['message'],
            ];

            // Send email to the specified address
            Mail::to('capstonevirgel@gmail.com')->send(new ContactFormMail($formData));

            return response()->json([
                'message' => 'Thank you for your message! We\'ll get back to you within 24 hours.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry, there was an error sending your message. Please try again later.'
            ], 500);
        }
    }
}

