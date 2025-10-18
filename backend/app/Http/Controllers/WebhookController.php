<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\PayMongoService;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Ticket;
use App\Models\ProductVariant;
use App\Models\PurchaseItem;
use App\Models\Cart;
use App\Mail\ProductPurchaseConfirmation;
use App\Mail\TicketPurchaseConfirmation;
use Illuminate\Support\Facades\Mail;

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
        // Check if this is a PayMongo webhook
        if ($request->hasHeader('paymongo-signature') || $request->hasHeader('PayMongo-Signature')) {
            $data = $request->all();
            
            // Log webhook data for debugging
            Log::info('PayMongo webhook received', [
                'headers' => $request->headers->all(),
                'data_structure' => $data
            ]);
            
            $eventType = $data['data']['attributes']['type'] ?? 'unknown';
            return $this->handlePayMongoWebhook($request, $eventType);
        }
        
        // Handle different webhook types
        $webhookType = $request->header('X-Webhook-Type') ?? 'unknown';
        
        switch ($webhookType) {
            case 'payment':
                return $this->handlePaymentWebhook($request);
            case 'subscription':
                return $this->handleSubscriptionWebhook($request);
            default:
                return $this->handleGenericWebhook($request, $webhookType);
        }
    }

    /**
     * Handle generic webhooks
     */
    private function handleGenericWebhook(Request $request, string $webhookType): JsonResponse
    {
        $data = $request->all();
        
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
            return response()->json([
                'error' => 'Invalid webhook signature'
            ], 401);
        }

        $data = $request->all();

        try {
            switch ($eventType) {
                case 'payment.paid':
                    return $this->handlePaymentPaid($data);
                case 'payment.failed':
                    return $this->handlePaymentFailed($data);
                default:
                    return response()->json([
                        'message' => 'Unhandled webhook event type',
                        'event_type' => $eventType
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('PayMongo webhook processing error', [
                'event_type' => $eventType,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
* Handle payment.paid webhook
     */
    private function handlePaymentPaid(array $data): JsonResponse
    {
        $paymentData = $data['data']['attributes'];
        
        // Log the webhook payload for debugging
        Log::info('PayMongo webhook payload', [
            'event_type' => 'payment.paid',
            'data_structure' => $data,
            'payment_data' => $paymentData
        ]);
        
        // Extract payment intent ID from different possible locations
        // PayMongo webhook structure can vary, so we check multiple locations
        $paymentIntentId = $paymentData['payment_intent_id'] ?? 
                          $paymentData['payment_intent']['id'] ?? 
                          $paymentData['data']['attributes']['payment_intent_id'] ?? 
                          $data['data']['id'] ?? 
                          $data['data']['attributes']['payment_intent_id'] ?? 
                          null;
                          
        $metadata = $paymentData['data']['attributes']['metadata'] ?? $paymentData['metadata'] ?? [];

        // Log extracted values for debugging
        Log::info('PayMongo webhook extracted values', [
            'event_type' => 'payment.paid',
            'payment_intent_id' => $paymentIntentId,
            'metadata' => $metadata
        ]);

        try {
            // Check if we have a payment intent ID
            if (!$paymentIntentId) {
                Log::warning('PayMongo webhook missing payment intent ID', [
                    'event_type' => 'payment.paid',
                    'data' => $data,
                    'payment_data' => $paymentData
                ]);
                
                return response()->json([
                    'error' => 'Payment intent ID not found in webhook payload',
                    'message' => 'Unable to process webhook without payment intent ID'
                ], 400);
            }
            
            // Find the payment record
            $paymentRecord = Payment::where('payment_intent_id', $paymentIntentId)->first();
            
            // Fallback: try to find by purchase_id in metadata
            if (!$paymentRecord && isset($metadata['purchase_id'])) {
                $paymentRecord = Payment::where('purchase_id', $metadata['purchase_id'])->first();
            }
            
            // Additional fallback: try to find by transaction_id or payment ID
            if (!$paymentRecord) {
                $paymentId = $data['data']['id'] ?? null;
                if ($paymentId) {
                    $paymentRecord = Payment::where('transaction_id', $paymentId)->first();
                }
            }

            // Log if no payment record found
            if (!$paymentRecord) {
                Log::warning('PayMongo webhook: Payment record not found', [
                    'event_type' => 'payment.paid',
                    'payment_intent_id' => $paymentIntentId,
                    'payment_id' => $data['data']['id'] ?? null,
                    'metadata' => $metadata
                ]);
                
                return response()->json([
                    'message' => 'Payment record not found',
                    'payment_intent_id' => $paymentIntentId
                ], 404);
            }

            if ($paymentRecord) {
                // Update payment status
                $paymentRecord->update([
                    'status' => 'paid',
                    'payment_date' => now(),
                    'transaction_id' => $data['data']['id'] ?? null,
                    'metadata' => json_encode($data)
                ]);

                // Update purchase status and handle business logic
                $purchase = $paymentRecord->purchase;
                if ($purchase) {
                    try {
                        DB::beginTransaction();
                        
                        $purchase->update(['status' => 'processing']);
                        
                        // Handle product purchases
                        if ($purchase->type === 'product') {
                            $this->decrementProductStock($purchase);
                            $this->sendProductConfirmationEmail($purchase);
                        }
                        
                        // Handle ticket purchases
                        if ($purchase->type === 'ticket') {
                            $this->createTicketsForPurchase($purchase);
                            $this->sendTicketConfirmationEmail($purchase);
                        }
                        
                        // Clear user's cart
                        Cart::where('user_id', $purchase->user_id)->delete();
                        
                        DB::commit();
                        
                    } catch (\Exception $e) {
                        DB::rollback();
                        throw $e;
                    }
                }
            }

            return response()->json([
                'message' => 'Payment processed successfully',
                'payment_intent_id' => $paymentIntentId
            ]);

        } catch (\Exception $e) {
            Log::error('Payment processing error', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Payment processing failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle payment.failed webhook
     */
    private function handlePaymentFailed(array $data): JsonResponse
    {
        $paymentData = $data['data']['attributes'];
        
        // Log the webhook payload for debugging
        Log::info('PayMongo webhook payload', [
            'event_type' => 'payment.failed',
            'data_structure' => $data,
            'payment_data' => $paymentData
        ]);
        
        // Extract payment intent ID from different possible locations
        // PayMongo webhook structure can vary, so we check multiple locations
        $paymentIntentId = $paymentData['payment_intent_id'] ?? 
                          $paymentData['payment_intent']['id'] ?? 
                          $paymentData['data']['attributes']['payment_intent_id'] ?? 
                          $data['data']['id'] ?? 
                          $data['data']['attributes']['payment_intent_id'] ?? 
                          null;
                          
        $metadata = $paymentData['data']['attributes']['metadata'] ?? $paymentData['metadata'] ?? [];

        try {
            // Check if we have a payment intent ID
            if (!$paymentIntentId) {
                Log::warning('PayMongo webhook missing payment intent ID', [
                    'event_type' => 'payment.failed',
                    'data' => $data,
                    'payment_data' => $paymentData
                ]);
                
                return response()->json([
                    'error' => 'Payment intent ID not found in webhook payload',
                    'message' => 'Unable to process webhook without payment intent ID'
                ], 400);
            }
            
            // Find the payment record
            $paymentRecord = Payment::where('payment_intent_id', $paymentIntentId)->first();
            
            // Fallback: try to find by purchase_id in metadata
            if (!$paymentRecord && isset($metadata['purchase_id'])) {
                $paymentRecord = Payment::where('purchase_id', $metadata['purchase_id'])->first();
            }
            
            // Additional fallback: try to find by transaction_id or payment ID
            if (!$paymentRecord) {
                $paymentId = $data['data']['id'] ?? null;
                if ($paymentId) {
                    $paymentRecord = Payment::where('transaction_id', $paymentId)->first();
                }
            }

            if ($paymentRecord) {
                // Update payment status
                $paymentRecord->update([
                    'status' => 'failed',
                    'payment_failure_code' => $paymentData['failure_code'] ?? null,
                    'payment_failure_message' => $paymentData['failure_message'] ?? null,
                    'metadata' => json_encode($data)
                ]);

                // Update purchase status to cancelled
                $purchase = $paymentRecord->purchase;
                if ($purchase) {
                    $purchase->update(['status' => 'cancelled']);
                }
            }

            return response()->json([
                'message' => 'Payment failure processed',
                'payment_intent_id' => $paymentIntentId
            ]);

        } catch (\Exception $e) {
            Log::error('Payment failure processing error', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Payment failure processing failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create tickets for a purchase
     */
    private function createTicketsForPurchase(Purchase $purchase): void
    {
        try {
            // Check if tickets already exist to prevent duplicates
            $existingTicketsCount = Ticket::where('purchase_id', $purchase->id)->count();
            if ($existingTicketsCount > 0) {
                return; // Tickets already exist, skip creation
            }

            $purchaseItem = $purchase->purchaseItems()->first();
            if (!$purchaseItem) {
                return;
            }

            $quantity = $purchaseItem->quantity;
            $event = $purchase->event;
            
            if (!$event) {
                return;
            }

            // Verify ticket availability
            if (!$event->hasTicketsAvailable($quantity)) {
                throw new \Exception('Not enough tickets available');
            }

            // Create tickets
            for ($i = 0; $i < $quantity; $i++) {
                $ticket = new Ticket();
                $ticket->purchase_id = $purchase->id;
                $ticket->event_id = $event->id;
                $ticket->user_id = $purchase->user_id;
                $ticket->status = 'confirmed';
                $ticket->ticket_number = $ticket->generateTicketNumber();
                $ticket->price = $event->ticket_price;
                $ticket->qr_code = $ticket->generateQRCode();
                $ticket->booked_at = now();
                $ticket->save();
            }

        } catch (\Exception $e) {
            Log::error('Failed to create tickets for purchase', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Decrement product stock for a purchase
     */
    private function decrementProductStock(Purchase $purchase): void
    {
        try {
            $purchaseItems = $purchase->purchaseItems;
            
            if ($purchaseItems->isEmpty()) {
                return;
            }

            foreach ($purchaseItems as $item) {
                if (!$item->variant) {
                    continue;
                }

                $variant = $item->variant;
                $success = $variant->decrementStock($item->quantity);

                if (!$success) {
                    throw new \Exception("Insufficient stock for variant {$variant->id}");
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to decrement product stock', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send product purchase confirmation email
     */
    private function sendProductConfirmationEmail(Purchase $purchase): void
    {
        try {
            $user = $purchase->user;
            if ($user && $user->email) {
                Mail::to($user->email)->send(new ProductPurchaseConfirmation($purchase, $user));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send product purchase confirmation email', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send ticket purchase confirmation email
     */
    private function sendTicketConfirmationEmail(Purchase $purchase): void
    {
        try {
            $user = $purchase->user;
            if ($user && $user->email) {
                Mail::to($user->email)->send(new TicketPurchaseConfirmation($purchase, $user));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send ticket purchase confirmation email', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle payment webhooks
     */
    private function handlePaymentWebhook(Request $request): JsonResponse
    {
        $data = $request->all();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Payment webhook processed',
            'data' => $data
        ]);
    }

    /**
     * Handle subscription webhooks
     */
    private function handleSubscriptionWebhook(Request $request): JsonResponse
    {
        $data = $request->all();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription webhook processed',
            'data' => $data
        ]);
    }
}