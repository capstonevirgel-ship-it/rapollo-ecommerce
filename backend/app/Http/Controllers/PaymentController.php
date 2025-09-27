<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class PaymentController extends Controller
{
    /**
     * Create payment for COD and other basic payment methods
     */
    public function createPayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:3',
            'payment_method' => 'required|string|in:card,gcash,paymaya,cod',
            'purchase_id' => 'required|integer|exists:purchases,id',
        ]);

        try {
            $purchase = Purchase::find($validated['purchase_id']);
            
            // Check if user owns this purchase
            if ($purchase->user_id !== Auth::id()) {
                return response()->json([
                    'error' => 'Unauthorized access to purchase.',
                ], 403);
            }

            // Check if purchase is in a valid state for payment
            if (in_array($purchase->status, ['completed', 'cancelled'])) {
                return response()->json([
                    'error' => "Cannot proceed with payment. Purchase status is {$purchase->status}.",
                ], 400);
            }

            // Create payment record
            $payment = Payment::updateOrCreate(
                ['purchase_id' => $validated['purchase_id']],
                [
                    'user_id' => Auth::id(),
                    'purchase_id' => $validated['purchase_id'],
                    'amount' => $validated['amount'],
                    'currency' => $validated['currency'],
                    'status' => $validated['payment_method'] === 'cod' ? 'pending' : 'paid',
                    'payment_method' => $validated['payment_method'],
                    'transaction_id' => $validated['payment_method'] === 'cod' ? null : 'sim_' . uniqid(),
                    'payment_date' => now(),
                    'notes' => $validated['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Payment completed',
                ]
            );

            // Update purchase status
            if ($validated['payment_method'] === 'cod') {
                $purchase->update(['status' => 'processing']);
            } else {
                $purchase->update(['status' => 'completed']);
                // Clear user's cart after successful payment
                Cart::where('user_id', Auth::id())->delete();
            }

            return response()->json([
                'message' => 'Payment processed successfully.',
                'payment_status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'purchase_status' => $purchase->status,
            ]);
        } catch (Exception $e) {
            Log::error('Error in createPayment:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}