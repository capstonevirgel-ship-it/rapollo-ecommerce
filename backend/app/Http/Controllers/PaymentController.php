<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\ProductPurchase;
use App\Models\TicketPurchase;
use App\Models\Cart;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class PaymentController extends Controller
{
    protected $payMongoService;

    public function __construct(PayMongoService $payMongoService)
    {
        $this->payMongoService = $payMongoService;
    }

    /**
     * Create PayMongo payment intent
     */
    public function createPaymentIntent(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:3',
            'purchasable_type' => 'required|string|in:App\Models\ProductPurchase,App\Models\TicketPurchase',
            'purchasable_id' => 'required|integer',
            'metadata' => 'sometimes|array'
        ]);

        try {
            // Find the purchasable (ProductPurchase or TicketPurchase)
            $purchasable = $validated['purchasable_type']::find($validated['purchasable_id']);
            
            if (!$purchasable) {
                return response()->json([
                    'error' => 'Purchase not found.',
                ], 404);
            }
            
            // Check if user owns this purchase
            if ($purchasable->user_id !== Auth::id()) {
                return response()->json([
                    'error' => 'Unauthorized access to purchase.',
                ], 403);
            }

            // Check if purchase is in a valid state for payment
            if ($purchasable instanceof ProductPurchase) {
                if (in_array($purchasable->status, ['completed', 'cancelled'])) {
                return response()->json([
                        'error' => "Cannot proceed with payment. Purchase status is {$purchasable->status}.",
                ], 400);
                }
            }

            // Add purchase info to metadata
            $metadata = array_merge($validated['metadata'] ?? [], [
                'purchase_id' => $purchasable->id,
                'user_id' => Auth::id(),
                'order_number' => 'ORD-' . $purchasable->id
            ]);

            // Create checkout session with PayMongo
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            $successUrl = $frontendUrl . '/checkout/success';
            
            $checkoutSession = $this->payMongoService->createCheckoutSession(
                $validated['amount'],
                $validated['currency'],
                $metadata,
                $successUrl,
                null
            );

            if (!$checkoutSession) {
                return response()->json([
                    'error' => 'Failed to create checkout session.',
                ], 500);
            }

            // Create payment record
            $payment = Payment::updateOrCreate(
                [
                    'purchasable_type' => $validated['purchasable_type'],
                    'purchasable_id' => $validated['purchasable_id']
                ],
                [
                    'user_id' => Auth::id(),
                    'purchasable_type' => $validated['purchasable_type'],
                    'purchasable_id' => $validated['purchasable_id'],
                    'amount' => $validated['amount'],
                    'currency' => $validated['currency'],
                    'status' => 'pending',
                    'payment_method' => 'paymongo',
                    'transaction_id' => $checkoutSession['data']['id'],
                    'payment_date' => null,
                    'notes' => 'PayMongo checkout session created',
                    'metadata' => json_encode($checkoutSession)
                ]
            );

            // Get the checkout URL from PayMongo
            $checkoutUrl = $checkoutSession['data']['attributes']['checkout_url'] ?? null;

            if (!$checkoutUrl) {
                return response()->json([
                    'error' => 'Checkout URL not provided by PayMongo.',
                ], 500);
            }

            return response()->json([
                'message' => 'Checkout session created successfully.',
                'payment_url' => $checkoutUrl,
                'checkout_session' => $checkoutSession,
                'payment_id' => $payment->id
            ]);

        } catch (Exception $e) {
            Log::error('Error in createPaymentIntent:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm PayMongo payment
     */
    public function confirmPayment(Request $request)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
            'payment_method_id' => 'required|string',
            'purchasable_type' => 'required|string|in:App\Models\ProductPurchase,App\Models\TicketPurchase',
            'purchasable_id' => 'required|integer'
        ]);

        try {
            // Find the purchasable (ProductPurchase or TicketPurchase)
            $purchasable = $validated['purchasable_type']::find($validated['purchasable_id']);
            
            if (!$purchasable) {
                return response()->json([
                    'error' => 'Purchase not found.',
                ], 404);
            }
            
            // Check if user owns this purchase
            if ($purchasable->user_id !== Auth::id()) {
                return response()->json([
                    'error' => 'Unauthorized access to purchase.',
                ], 403);
            }

            // Attach payment method to payment intent
            $result = $this->payMongoService->attachPaymentMethod(
                $validated['payment_intent_id'],
                $validated['payment_method_id']
            );

            if (!$result) {
                return response()->json([
                    'error' => 'Failed to confirm payment.',
                ], 500);
            }

            // Get updated payment intent
            $paymentIntent = $this->payMongoService->getPaymentIntent($validated['payment_intent_id']);
            
            if (!$paymentIntent) {
                return response()->json([
                    'error' => 'Failed to retrieve payment status.',
                ], 500);
            }

            $status = $paymentIntent['data']['attributes']['status'];
            $payment = $paymentIntent['data']['attributes']['payments'][0] ?? null;

            // Update payment record
            $paymentRecord = Payment::where('transaction_id', $validated['payment_intent_id'])->first();
            if ($paymentRecord) {
                $paymentRecord->update([
                    'status' => $status === 'succeeded' ? 'paid' : 'failed',
                    'payment_date' => $status === 'succeeded' ? now() : null,
                    'notes' => $status === 'succeeded' ? 'Payment confirmed via PayMongo' : 'Payment failed',
                    'metadata' => json_encode($paymentIntent)
                ]);
            }

            // Update purchase status
            if ($status === 'succeeded') {
                if ($purchasable instanceof ProductPurchase) {
                    $purchasable->update(['status' => 'processing', 'paid_at' => now()]);
                // Clear user's cart after successful payment
                Cart::where('user_id', Auth::id())->delete();
                } elseif ($purchasable instanceof TicketPurchase) {
                    $purchasable->update(['paid_at' => now()]);
                }
            }

            return response()->json([
                'message' => 'Payment processed successfully.',
                'status' => $status,
                'payment_intent' => $paymentIntent,
                'purchase_status' => $purchasable instanceof ProductPurchase ? $purchasable->fresh()->status : 'paid'
            ]);

        } catch (Exception $e) {
            Log::error('Error in confirmPayment:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create refund
     */
    public function createRefund(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'sometimes|string|in:duplicate,requested_by_customer,fraudulent'
        ]);

        try {
            $refund = $this->payMongoService->createRefund(
                $validated['payment_id'],
                $validated['amount'],
                $validated['reason'] ?? 'requested_by_customer'
            );

            if (!$refund) {
                return response()->json([
                    'error' => 'Failed to create refund.',
                ], 500);
            }

            return response()->json([
                'message' => 'Refund created successfully.',
                'refund' => $refund
            ]);

        } catch (Exception $e) {
            Log::error('Error in createRefund:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create payment for COD and other basic payment methods
     */
    public function createPayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:3',
            'payment_method' => 'required|string|in:card,gcash,paymaya,cod',
            'purchasable_type' => 'required|string|in:App\Models\ProductPurchase,App\Models\TicketPurchase',
            'purchasable_id' => 'required|integer',
        ]);

        try {
            // Find the purchasable (ProductPurchase or TicketPurchase)
            $purchasable = $validated['purchasable_type']::find($validated['purchasable_id']);
            
            if (!$purchasable) {
                return response()->json([
                    'error' => 'Purchase not found.',
                ], 404);
            }
            
            // Check if user owns this purchase
            if ($purchasable->user_id !== Auth::id()) {
                return response()->json([
                    'error' => 'Unauthorized access to purchase.',
                ], 403);
            }

            // Check if purchase is in a valid state for payment
            if ($purchasable instanceof ProductPurchase) {
                if (in_array($purchasable->status, ['completed', 'cancelled'])) {
                return response()->json([
                        'error' => "Cannot proceed with payment. Purchase status is {$purchasable->status}.",
                ], 400);
                }
            }

            // Create payment record
            $payment = Payment::updateOrCreate(
                [
                    'purchasable_type' => $validated['purchasable_type'],
                    'purchasable_id' => $validated['purchasable_id']
                ],
                [
                    'user_id' => Auth::id(),
                    'purchasable_type' => $validated['purchasable_type'],
                    'purchasable_id' => $validated['purchasable_id'],
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
                if ($purchasable instanceof ProductPurchase) {
                    $purchasable->update(['status' => 'processing']);
                }
            } else {
                if ($purchasable instanceof ProductPurchase) {
                    $purchasable->update(['status' => 'processing', 'paid_at' => now()]);
                // Clear user's cart after successful payment
                Cart::where('user_id', Auth::id())->delete();
                } elseif ($purchasable instanceof TicketPurchase) {
                    $purchasable->update(['paid_at' => now()]);
                }
            }

            return response()->json([
                'message' => 'Payment processed successfully.',
                'payment_status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'purchase_status' => $purchasable instanceof ProductPurchase ? $purchasable->status : 'paid',
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