<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\PayMongoService;
use App\Models\Payment;
use App\Models\Purchase;

class WebhookController extends Controller
{
    protected $payMongoService;

    public function __construct(PayMongoService $payMongoService)
    {
        $this->payMongoService = $payMongoService;
    }

    /**
     * Handle incoming webhooks
     */
    public function handle(Request $request): JsonResponse
    {
        // Log the webhook data for debugging
        Log::info('Webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
        ]);

        // Check if this is a PayMongo webhook
        if ($request->hasHeader('paymongo-signature') || $request->hasHeader('PayMongo-Signature')) {
            $data = $request->all();
            $eventType = $data['data']['attributes']['type'] ?? 'unknown';
            return $this->handlePayMongoWebhook($request, $eventType);
        }
        
        // Handle different webhook types
        $webhookType = $request->header('X-Webhook-Type') ?? 'unknown';
        
        switch ($webhookType) {
            case 'payment':
                return $this->handlePaymentWebhook($request);
            case 'email':
                return $this->handleEmailWebhook($request);
            case 'order':
                return $this->handleOrderWebhook($request);
            default:
                return $this->handleGenericWebhook($request);
        }
    }

    /**
     * Handle payment webhooks
     */
    private function handlePaymentWebhook(Request $request): JsonResponse
    {
        // Process payment webhook
        $data = $request->all();
        
        // Example: Update payment status
        // Payment::where('transaction_id', $data['transaction_id'])
        //     ->update(['status' => $data['status']]);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment webhook processed',
            'data' => $data
        ]);
    }

    /**
     * Handle email webhooks
     */
    private function handleEmailWebhook(Request $request): JsonResponse
    {
        $data = $request->all();
        
        // Process email delivery status
        // Update email logs, handle bounces, etc.

        return response()->json([
            'status' => 'success',
            'message' => 'Email webhook processed',
            'data' => $data
        ]);
    }

    /**
     * Handle order webhooks
     */
    private function handleOrderWebhook(Request $request): JsonResponse
    {
        $data = $request->all();
        
        // Process order updates
        // Update order status, inventory, etc.

        return response()->json([
            'status' => 'success',
            'message' => 'Order webhook processed',
            'data' => $data
        ]);
    }

    /**
     * Handle generic webhooks
     */
    private function handleGenericWebhook(Request $request): JsonResponse
    {
        $data = $request->all();
        
        // Process any webhook data
        Log::info('Generic webhook processed', $data);

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook received and logged',
            'data' => $data,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Handle PayMongo webhooks
     */
    private function handlePayMongoWebhook(Request $request, string $eventType): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('PayMongo-Signature');
        
        // Verify webhook signature
        if (!$this->payMongoService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('PayMongo webhook signature verification failed', [
                'event_type' => $eventType,
                'signature' => $signature
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid webhook signature'
            ], 401);
        }

        $data = $request->all();
        Log::info('PayMongo webhook processed', [
            'event_type' => $eventType,
            'data' => $data
        ]);

        try {
            switch ($eventType) {
                case 'source.chargeable':
                    return $this->handleSourceChargeable($data);
                case 'payment.paid':
                    return $this->handlePaymentPaid($data);
                case 'payment.failed':
                    return $this->handlePaymentFailed($data);
                default:
                    Log::info('Unhandled PayMongo webhook event', [
                        'event_type' => $eventType,
                        'data' => $data
                    ]);
                    
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Webhook received but not processed',
                        'event_type' => $eventType
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('PayMongo webhook processing error', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Handle source.chargeable event
     */
    private function handleSourceChargeable(array $data): JsonResponse
    {
        $source = $data['data']['attributes']['source'] ?? null;
        $paymentIntentId = $data['data']['attributes']['payment_intent_id'] ?? null;

        if ($paymentIntentId) {
            // Update payment record
            $payment = Payment::where('transaction_id', $paymentIntentId)->first();
            if ($payment) {
                $payment->update([
                    'status' => 'processing',
                    'notes' => 'Payment source is chargeable',
                    'metadata' => json_encode($data)
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Source chargeable event processed',
            'payment_intent_id' => $paymentIntentId
        ]);
    }

    /**
     * Handle payment.paid event
     */
    private function handlePaymentPaid(array $data): JsonResponse
    {
        $paymentData = $data['data']['attributes']['data'] ?? null;
        $paymentIntentId = $paymentData['attributes']['payment_intent_id'] ?? null;
        $metadata = $paymentData['attributes']['metadata'] ?? [];

        Log::info('Processing payment.paid webhook', [
            'payment_intent_id' => $paymentIntentId,
            'metadata' => $metadata
        ]);

        if ($paymentIntentId) {
            // Try to find payment record by transaction_id first (for payment intents)
            $paymentRecord = Payment::where('transaction_id', $paymentIntentId)->first();
            
            // If not found, try to find by purchase_id from metadata (for checkout sessions)
            if (!$paymentRecord && isset($metadata['purchase_id'])) {
                $paymentRecord = Payment::where('purchase_id', $metadata['purchase_id'])->first();
                Log::info('Found payment record by purchase_id', [
                    'purchase_id' => $metadata['purchase_id'],
                    'payment_id' => $paymentRecord ? $paymentRecord->id : null
                ]);
            }
            
            if ($paymentRecord) {
                $paymentRecord->update([
                    'status' => 'paid',
                    'payment_date' => now(),
                    'notes' => 'Payment completed via PayMongo webhook',
                    'metadata' => json_encode($data)
                ]);

                // Update purchase status
                $purchase = Purchase::find($paymentRecord->purchase_id);
                if ($purchase) {
                    $purchase->update(['status' => 'processing']);
                    Log::info('Purchase status updated to processing', [
                        'purchase_id' => $purchase->id,
                        'payment_id' => $paymentRecord->id
                    ]);
                }
            } else {
                Log::warning('Payment record not found', [
                    'payment_intent_id' => $paymentIntentId,
                    'purchase_id' => $metadata['purchase_id'] ?? 'not provided'
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Payment paid event processed',
            'payment_intent_id' => $paymentIntentId
        ]);
    }

    /**
     * Handle payment.failed event
     */
    private function handlePaymentFailed(array $data): JsonResponse
    {
        $paymentData = $data['data']['attributes']['data'] ?? null;
        $paymentIntentId = $paymentData['attributes']['payment_intent_id'] ?? null;
        $metadata = $paymentData['attributes']['metadata'] ?? [];

        Log::info('Processing payment.failed webhook', [
            'payment_intent_id' => $paymentIntentId,
            'metadata' => $metadata
        ]);

        if ($paymentIntentId) {
            // Try to find payment record by transaction_id first (for payment intents)
            $paymentRecord = Payment::where('transaction_id', $paymentIntentId)->first();
            
            // If not found, try to find by purchase_id from metadata (for checkout sessions)
            if (!$paymentRecord && isset($metadata['purchase_id'])) {
                $paymentRecord = Payment::where('purchase_id', $metadata['purchase_id'])->first();
                Log::info('Found payment record by purchase_id for failed payment', [
                    'purchase_id' => $metadata['purchase_id'],
                    'payment_id' => $paymentRecord ? $paymentRecord->id : null
                ]);
            }
            
            if ($paymentRecord) {
                $paymentRecord->update([
                    'status' => 'failed',
                    'notes' => 'Payment failed via PayMongo webhook',
                    'metadata' => json_encode($data)
                ]);

                // Update purchase status
                $purchase = Purchase::find($paymentRecord->purchase_id);
                if ($purchase) {
                    $purchase->update(['status' => 'cancelled']);
                    Log::info('Purchase status updated to cancelled', [
                        'purchase_id' => $purchase->id,
                        'payment_id' => $paymentRecord->id
                    ]);
                }
            } else {
                Log::warning('Payment record not found for failed payment', [
                    'payment_intent_id' => $paymentIntentId,
                    'purchase_id' => $metadata['purchase_id'] ?? 'not provided'
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Payment failed event processed',
            'payment_intent_id' => $paymentIntentId
        ]);
    }


    /**
     * Test webhook endpoint
     */
    public function test(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Webhook endpoint is working!',
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'timestamp' => now()->toISOString(),
            'server' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
            ]
        ]);
    }
}
