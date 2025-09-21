<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Cart;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayMongoWebhookService
{
    /**
     * Process PayMongo webhook events
     */
    public function processEvent(array $payload): array
    {
        try {
            $eventType = $payload['data']['type'] ?? null;
            $eventData = $payload['data']['attributes'] ?? [];

            switch ($eventType) {
                case 'payment.paid':
                    return $this->handlePaymentPaid($eventData);
                
                case 'payment.failed':
                    return $this->handlePaymentFailed($eventData);
                
                case 'payment.cancelled':
                    return $this->handlePaymentCancelled($eventData);
                
                default:
                    Log::info('PayMongo webhook: Unhandled event type', [
                        'event_type' => $eventType,
                        'data' => $eventData
                    ]);
                    return ['success' => true, 'message' => 'Event type not handled'];
            }
        } catch (\Exception $e) {
            Log::error('PayMongo webhook processing error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $payload
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentPaid(array $eventData): array
    {
        try {
            DB::beginTransaction();

            $paymentIntentId = $eventData['payment_intent_id'] ?? null;
            $amount = $eventData['amount'] ?? 0;
            $currency = $eventData['currency'] ?? 'PHP';
            $status = $eventData['status'] ?? 'paid';

            // Find the purchase by payment intent ID
            $purchase = Purchase::where('payment_intent_id', $paymentIntentId)->first();

            if (!$purchase) {
                Log::warning('PayMongo webhook: Purchase not found', [
                    'payment_intent_id' => $paymentIntentId,
                    'event_data' => $eventData
                ]);
                return ['success' => false, 'error' => 'Purchase not found'];
            }

            // Update purchase status
            $purchase->update([
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_amount' => $amount,
                'payment_currency' => $currency,
                'paid_at' => now(),
            ]);

            // Clear the user's cart
            Cart::where('user_id', $purchase->user_id)->delete();

            // Update product stock
            $this->updateProductStock($purchase);

            DB::commit();

            Log::info('PayMongo webhook: Payment processed successfully', [
                'purchase_id' => $purchase->id,
                'payment_intent_id' => $paymentIntentId,
                'amount' => $amount
            ]);

            return ['success' => true, 'message' => 'Payment processed successfully'];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PayMongo webhook: Payment processing failed', [
                'message' => $e->getMessage(),
                'event_data' => $eventData
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed(array $eventData): array
    {
        try {
            $paymentIntentId = $eventData['payment_intent_id'] ?? null;
            $failureCode = $eventData['failure_code'] ?? null;
            $failureMessage = $eventData['failure_message'] ?? 'Payment failed';

            $purchase = Purchase::where('payment_intent_id', $paymentIntentId)->first();

            if ($purchase) {
                $purchase->update([
                    'status' => 'failed',
                    'payment_status' => 'failed',
                    'payment_failure_code' => $failureCode,
                    'payment_failure_message' => $failureMessage,
                ]);

                Log::info('PayMongo webhook: Payment failed', [
                    'purchase_id' => $purchase->id,
                    'payment_intent_id' => $paymentIntentId,
                    'failure_code' => $failureCode,
                    'failure_message' => $failureMessage
                ]);
            }

            return ['success' => true, 'message' => 'Payment failure processed'];

        } catch (\Exception $e) {
            Log::error('PayMongo webhook: Payment failure processing error', [
                'message' => $e->getMessage(),
                'event_data' => $eventData
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle cancelled payment
     */
    private function handlePaymentCancelled(array $eventData): array
    {
        try {
            $paymentIntentId = $eventData['payment_intent_id'] ?? null;

            $purchase = Purchase::where('payment_intent_id', $paymentIntentId)->first();

            if ($purchase) {
                $purchase->update([
                    'status' => 'cancelled',
                    'payment_status' => 'cancelled',
                ]);

                Log::info('PayMongo webhook: Payment cancelled', [
                    'purchase_id' => $purchase->id,
                    'payment_intent_id' => $paymentIntentId
                ]);
            }

            return ['success' => true, 'message' => 'Payment cancellation processed'];

        } catch (\Exception $e) {
            Log::error('PayMongo webhook: Payment cancellation processing error', [
                'message' => $e->getMessage(),
                'event_data' => $eventData
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update product stock after successful payment
     */
    private function updateProductStock(Purchase $purchase): void
    {
        $purchaseItems = $purchase->purchaseItems;

        foreach ($purchaseItems as $item) {
            $productVariant = ProductVariant::find($item->product_variant_id);
            
            if ($productVariant) {
                $productVariant->decrement('stock', $item->quantity);
                
                Log::info('PayMongo webhook: Stock updated', [
                    'product_variant_id' => $productVariant->id,
                    'quantity_sold' => $item->quantity,
                    'remaining_stock' => $productVariant->fresh()->stock
                ]);
            }
        }
    }
}
