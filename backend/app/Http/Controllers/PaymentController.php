<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Cart;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class PaymentController extends Controller
{
    protected $paymongoService;

    public function __construct(PayMongoService $paymongoService)
    {
        $this->paymongoService = $paymongoService;
    }

    /**
     * Create PayMongo payment intent and redirect to gateway
     */
    public function createPayMongoPayment(Request $request)
    {
        $validated = $request->validate([
            'cart_data' => 'required|array',
            'cart_data.items' => 'required|array|min:1',
            'cart_data.items.*.product_variant_id' => 'required|integer|exists:product_variants,id',
            'cart_data.items.*.quantity' => 'required|integer|min:1',
            'cart_data.items.*.price' => 'required|numeric|min:0.01',
            'shipping_address' => 'nullable|array',
            'billing_address' => 'nullable|array',
        ]);

        try {
            $result = $this->paymongoService->createPaymentIntent($validated['cart_data'], Auth::id());

            if ($result['success']) {
                return response()->json([
                    'message' => 'Payment intent created successfully.',
                    'payment_intent_id' => $result['payment_intent_id'],
                    'client_key' => $result['client_key'],
                    'purchase_id' => $result['purchase_id'],
                    'redirect_url' => $result['redirect_url'],
                ]);
            } else {
                return response()->json([
                    'error' => $result['error'],
                ], 400);
            }
        } catch (Exception $e) {
            Log::error('Error in createPayMongoPayment:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify PayMongo payment status
     */
    public function verifyPayMongoPayment(Request $request)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        try {
            $result = $this->paymongoService->verifyPayment($validated['payment_intent_id']);

            if ($result['success']) {
                return response()->json([
                    'status' => $result['status'],
                    'amount' => $result['amount'],
                    'currency' => $result['currency'],
                ]);
            } else {
                return response()->json([
                    'error' => $result['error'],
                ], 400);
            }
        } catch (Exception $e) {
            Log::error('Error in verifyPayMongoPayment:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Legacy payment method for COD and other non-PayMongo payments
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
