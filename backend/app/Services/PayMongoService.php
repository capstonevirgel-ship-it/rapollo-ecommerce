<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PayMongo\PayMongo;

class PayMongoService
{
    protected $paymongo;

    public function __construct()
    {
        $this->paymongo = new PayMongo(config('services.paymongo.secret_key'));
    }

    /**
     * Create a payment intent and redirect to PayMongo gateway
     */
    public function createPaymentIntent(array $cartData, int $userId): array
    {
        try {
            DB::beginTransaction();

            // Create purchase record first
            $purchase = $this->createPurchaseRecord($cartData, $userId);

            // Create payment intent with PayMongo
            $paymentIntent = $this->paymongo->paymentIntents->create([
                'amount' => $purchase->total_amount * 100, // Convert to centavos
                'currency' => 'PHP',
                'payment_method_allowed' => ['card', 'gcash', 'grab_pay'],
                'metadata' => [
                    'purchase_id' => $purchase->id,
                    'user_id' => $userId,
                ],
            ]);

            // Update purchase with payment intent ID
            $purchase->update([
                'payment_intent_id' => $paymentIntent['data']['id'],
                'status' => 'pending_payment',
            ]);

            DB::commit();

            return [
                'success' => true,
                'payment_intent_id' => $paymentIntent['data']['id'],
                'client_key' => $paymentIntent['data']['attributes']['client_key'],
                'purchase_id' => $purchase->id,
                'redirect_url' => $this->getRedirectUrl($paymentIntent['data']['id']),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PayMongo payment intent creation failed', [
                'message' => $e->getMessage(),
                'user_id' => $userId,
                'cart_data' => $cartData
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create purchase record from cart data
     */
    private function createPurchaseRecord(array $cartData, int $userId): Purchase
    {
        $totalAmount = 0;
        $purchaseItems = [];

        // Calculate total and prepare purchase items
        foreach ($cartData['items'] as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $totalAmount += $subtotal;

            $purchaseItems[] = [
                'product_variant_id' => $item['product_variant_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $subtotal,
            ];
        }

        // Create purchase
        $purchase = Purchase::create([
            'user_id' => $userId,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'payment_status' => 'pending',
            'shipping_address' => $cartData['shipping_address'] ?? null,
            'billing_address' => $cartData['billing_address'] ?? null,
        ]);

        // Create purchase items
        foreach ($purchaseItems as $item) {
            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_variant_id' => $item['product_variant_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $item['subtotal'],
            ]);
        }

        return $purchase;
    }

    /**
     * Get redirect URL for PayMongo gateway
     */
    private function getRedirectUrl(string $paymentIntentId): string
    {
        $baseUrl = config('services.paymongo.base_url');
        return "{$baseUrl}/v1/payment_intents/{$paymentIntentId}/confirm";
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $paymentIntentId): array
    {
        try {
            $paymentIntent = $this->paymongo->paymentIntents->retrieve($paymentIntentId);
            
            return [
                'success' => true,
                'status' => $paymentIntent['data']['attributes']['status'],
                'amount' => $paymentIntent['data']['attributes']['amount'],
                'currency' => $paymentIntent['data']['attributes']['currency'],
            ];
        } catch (\Exception $e) {
            Log::error('PayMongo payment verification failed', [
                'message' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
