<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\PayMongoService;
use App\Models\Payment;
use App\Models\ProductPurchase;
use App\Models\TicketPurchase;
use App\Models\Ticket;
use App\Models\ProductVariant;
use App\Models\ProductPurchaseItem;
use App\Models\Cart;
use App\Models\User;
use App\Mail\ProductPurchaseConfirmation;
use App\Mail\TicketPurchaseConfirmation;
use App\Services\NotificationService;
use App\Mail\PaymentFailureNotification;
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
                          
        // Extract metadata from payment intent (where it's actually stored)
        $metadata = [];
        if (isset($paymentData['data']['attributes']['payment_intent']['attributes']['metadata'])) {
            $metadata = $paymentData['data']['attributes']['payment_intent']['attributes']['metadata'];
        } elseif (isset($paymentData['data']['attributes']['metadata'])) {
            $metadata = $paymentData['data']['attributes']['metadata'];
        } elseif (isset($paymentData['metadata'])) {
            $metadata = $paymentData['metadata'];
        }

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
            
            // Fallback: try to find by purchasable_id in metadata
            if (!$paymentRecord && isset($metadata['purchase_id'])) {
                // Try to find by purchasable_id (could be product_purchase_id or ticket_purchase_id)
                $purchaseId = $metadata['purchase_id'];
                $paymentRecord = Payment::where(function($q) use ($purchaseId) {
                    $q->where(function($q2) use ($purchaseId) {
                        $q2->where('purchasable_type', ProductPurchase::class)
                           ->where('purchasable_id', $purchaseId);
                    })->orWhere(function($q2) use ($purchaseId) {
                        $q2->where('purchasable_type', TicketPurchase::class)
                           ->where('purchasable_id', $purchaseId);
                    });
                })->first();
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
                $purchasable = $paymentRecord->purchasable;
                if ($purchasable) {
                    try {
                        DB::beginTransaction();
                        
                        // Handle product purchases
                        if ($purchasable instanceof ProductPurchase) {
                            $purchasable->update(['status' => 'processing']);
                            $this->decrementProductStock($purchasable);
                            $this->sendProductConfirmationEmail($purchasable);
                            $this->createProductOrderNotifications($purchasable);
                        
                        // Clear only purchased items from user's cart (preserve other items)
                        try {
                                $purchasedVariantIds = $purchasable->productPurchaseItems()
                                ->pluck('variant_id')
                                ->filter()
                                ->all();
                            if (!empty($purchasedVariantIds)) {
                                    Cart::where('user_id', $purchasable->user_id)
                                    ->whereIn('variant_id', $purchasedVariantIds)
                                    ->delete();
                            }
                        } catch (\Exception $e) {
                            // If anything goes wrong, fallback to clearing entire cart to avoid inconsistencies
                            Log::warning('Selective cart clear failed, falling back to full clear', [
                                    'purchase_id' => $purchasable->id,
                                    'user_id' => $purchasable->user_id,
                                'error' => $e->getMessage(),
                            ]);
                                Cart::where('user_id', $purchasable->user_id)->delete();
                            }
                        }
                        
                        // Handle ticket purchases
                        if ($purchasable instanceof TicketPurchase) {
                            $purchasable->update(['paid_at' => now()]);
                            $this->createTicketsForPurchase($purchasable);
                            $this->sendTicketConfirmationEmail($purchasable);
                            $this->createEventOrderNotifications($purchasable);
                        }
                        
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
                          
        // Extract metadata from payment intent (where it's actually stored)
        $metadata = [];
        if (isset($paymentData['data']['attributes']['payment_intent']['attributes']['metadata'])) {
            $metadata = $paymentData['data']['attributes']['payment_intent']['attributes']['metadata'];
        } elseif (isset($paymentData['data']['attributes']['metadata'])) {
            $metadata = $paymentData['data']['attributes']['metadata'];
        } elseif (isset($paymentData['metadata'])) {
            $metadata = $paymentData['metadata'];
        }

        // Log extracted values for debugging
        Log::info('PayMongo webhook extracted values', [
            'event_type' => 'payment.failed',
            'payment_intent_id' => $paymentIntentId,
            'metadata' => $metadata,
            'payment_id' => $data['data']['id'] ?? null,
            'debug_payment_data_structure' => [
                'has_payment_intent' => isset($paymentData['data']['attributes']['payment_intent']),
                'payment_intent_id_in_payment_intent' => $paymentData['data']['attributes']['payment_intent']['id'] ?? 'not_found',
                'metadata_in_payment_intent' => $paymentData['data']['attributes']['payment_intent']['attributes']['metadata'] ?? 'not_found',
                'metadata_in_payment' => $paymentData['data']['attributes']['metadata'] ?? 'not_found'
            ]
        ]);

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
            
            // Find the payment record by payment_intent_id first
            $paymentRecord = Payment::where('payment_intent_id', $paymentIntentId)->first();
            
            // Fallback: try to find by purchasable_id in metadata (this is the most reliable)
            if (!$paymentRecord && isset($metadata['purchase_id'])) {
                $purchaseId = $metadata['purchase_id'];
                $paymentRecord = Payment::where(function($q) use ($purchaseId) {
                    $q->where(function($q2) use ($purchaseId) {
                        $q2->where('purchasable_type', ProductPurchase::class)
                           ->where('purchasable_id', $purchaseId);
                    })->orWhere(function($q2) use ($purchaseId) {
                        $q2->where('purchasable_type', TicketPurchase::class)
                           ->where('purchasable_id', $purchaseId);
                    });
                })->first();
                Log::info('Found payment record by purchasable_id', [
                    'purchase_id' => $purchaseId,
                    'payment_id' => $paymentRecord ? $paymentRecord->id : null
                ]);
            }
            
            // Additional fallback: try to find by payment intent ID in metadata JSON
            if (!$paymentRecord) {
                // Look for payment records where metadata contains the payment_intent_id
                $paymentRecord = Payment::whereRaw("JSON_EXTRACT(metadata, '$.data.attributes.payment_intent.id') = ?", [$paymentIntentId])->first();
                Log::info('Found payment record by payment_intent_id in metadata', [
                    'payment_intent_id' => $paymentIntentId,
                    'payment_id' => $paymentRecord ? $paymentRecord->id : null
                ]);
            }
            
            // Final fallback: try to find by transaction_id (checkout session ID)
            if (!$paymentRecord) {
                $paymentId = $data['data']['id'] ?? null;
                if ($paymentId) {
                    $paymentRecord = Payment::where('transaction_id', $paymentId)->first();
                    Log::info('Found payment record by transaction_id', [
                        'transaction_id' => $paymentId,
                        'payment_id' => $paymentRecord ? $paymentRecord->id : null
                    ]);
                }
            }
            
            // Additional fallback: try to find by looking for recent payments with same amount
            if (!$paymentRecord) {
                $amount = $paymentData['data']['attributes']['amount'] ?? null;
                if ($amount) {
                    // Convert amount from cents to decimal (PayMongo sends amount in cents)
                    $amountInDecimal = $amount / 100;
                    $paymentRecord = Payment::where('amount', $amountInDecimal)
                        ->where('status', 'pending')
                        ->where('created_at', '>=', now()->subHours(2)) // Within last 2 hours
                        ->first();
                    Log::info('Found payment record by amount and recent timestamp', [
                        'amount' => $amountInDecimal,
                        'payment_id' => $paymentRecord ? $paymentRecord->id : null
                    ]);
                }
            }
            
            // Final fallback: try to find by payment intent ID in metadata and update it
            if (!$paymentRecord && isset($metadata['purchase_id'])) {
                $purchaseId = $metadata['purchase_id'];
                // Look for payment records with this purchasable_id and update payment_intent_id
                $paymentRecord = Payment::where(function($q) use ($purchaseId) {
                    $q->where(function($q2) use ($purchaseId) {
                        $q2->where('purchasable_type', ProductPurchase::class)
                           ->where('purchasable_id', $purchaseId);
                    })->orWhere(function($q2) use ($purchaseId) {
                        $q2->where('purchasable_type', TicketPurchase::class)
                           ->where('purchasable_id', $purchaseId);
                    });
                })->where('payment_intent_id', null)
                    ->first();
                
                if ($paymentRecord) {
                    // Update the payment record with the correct payment intent ID
                    $paymentRecord->update(['payment_intent_id' => $paymentIntentId]);
                    Log::info('Updated payment record with payment_intent_id', [
                        'payment_id' => $paymentRecord->id,
                        'payment_intent_id' => $paymentIntentId
                    ]);
                }
            }
            
            // Log payment record search results
            Log::info('Payment record search results', [
                'payment_intent_id' => $paymentIntentId,
                'found_record' => $paymentRecord ? true : false,
                'payment_record_id' => $paymentRecord ? $paymentRecord->id : null,
                'purchasable_id' => $paymentRecord ? $paymentRecord->purchasable_id : null
            ]);

            if ($paymentRecord) {
                // Update payment status and store payment_intent_id if not already stored
                $updateData = [
                    'status' => 'failed',
                    'payment_failure_code' => $paymentData['failure_code'] ?? null,
                    'payment_failure_message' => $paymentData['failure_message'] ?? null,
                    'metadata' => json_encode($data)
                ];
                
                // Store payment_intent_id if not already stored
                if (!$paymentRecord->payment_intent_id) {
                    $updateData['payment_intent_id'] = $paymentIntentId;
                }
                
                $paymentRecord->update($updateData);

                // Update purchase status to failed
                $purchasable = $paymentRecord->purchasable;
                if ($purchasable) {
                    if ($purchasable instanceof ProductPurchase) {
                        $oldStatus = $purchasable->status;
                        $purchasable->update(['status' => 'failed']);
                    
                        Log::info('Updated product purchase status to failed', [
                            'purchase_id' => $purchasable->id,
                        'old_status' => $oldStatus,
                        'new_status' => 'failed'
                    ]);
                    }
                    
                    // Create cancelled tickets for failed payment so user can see what they tried to purchase
                    if ($purchasable instanceof TicketPurchase) {
                        $this->createCancelledTicketsForPurchase($purchasable);
                    }
                    
                    // Create notification for the user about failed payment
                    $user = $purchasable->user;
                    if ($user) {
                        try {
                            // Create in-app notification
                            if ($purchasable instanceof TicketPurchase) {
                                NotificationService::createPaymentNotification(
                                    $user,
                                    'Payment Failed',
                                    'Your payment for event tickets has failed. Please try again.',
                                    [
                                        'action_url' => '/events',
                                        'action_text' => 'View Events'
                                    ]
                                );
                                Log::info('Created payment failed notification for ticket purchase', [
                                    'user_id' => $user->id,
                                    'purchase_id' => $purchasable->id
                                ]);
                            } else {
                                NotificationService::createPaymentNotification(
                                    $user,
                                    'Payment Failed',
                                    'Your payment for order #' . $purchasable->id . ' has failed. Please try again.',
                                    [
                                        'action_url' => '/my-orders',
                                        'action_text' => 'View Orders'
                                    ]
                                );
                                Log::info('Created payment failed notification for product purchase', [
                                    'user_id' => $user->id,
                                    'purchase_id' => $purchasable->id
                                ]);
                            }

                            // Send email notification
                            $failureReason = $paymentData['failure_message'] ?? 'Payment checkout has expired';
                            $failureCode = $paymentData['failure_code'] ?? 'CLOSED';
                            
                            Mail::to($user->email)->send(new PaymentFailureNotification(
                                $purchasable, 
                                $user, 
                                $failureReason, 
                                $failureCode
                            ));
                            
                            Log::info('Sent payment failure email notification', [
                                'user_id' => $user->id,
                                'user_email' => $user->email,
                                'purchase_id' => $purchasable->id,
                                'failure_reason' => $failureReason
                            ]);
                            
                        } catch (\Exception $e) {
                            Log::error('Failed to create payment failed notification or send email', [
                                'user_id' => $user->id,
                                'purchase_id' => $purchasable->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    } else {
                        Log::warning('No user found for purchase when creating payment failed notification', [
                            'purchase_id' => $purchasable->id
                        ]);
                    }
                }
            }

            Log::info('Payment failure webhook completed successfully', [
                'payment_intent_id' => $paymentIntentId,
                'payment_record_id' => $paymentRecord ? $paymentRecord->id : null,
                'purchasable_id' => $paymentRecord ? $paymentRecord->purchasable_id : null
            ]);

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
     * Create tickets for a ticket purchase
     */
    private function createTicketsForPurchase(TicketPurchase $purchase): void
    {
        try {
            // Check if tickets already exist to prevent duplicates
            $existingTicketsCount = Ticket::where('ticket_purchase_id', $purchase->id)->count();
            if ($existingTicketsCount > 0) {
                return; // Tickets already exist, skip creation
            }

            // Get quantity from metadata or tickets count
            // For ticket purchases, we need to get quantity from payment metadata or calculate from total
            $event = $purchase->event;
            if (!$event || !$event->ticket_price) {
                return;
            }

            $quantity = (int) ($purchase->total / $event->ticket_price);

            // Verify ticket availability
            if (!$event->hasTicketsAvailable($quantity)) {
                throw new \Exception('Not enough tickets available');
            }

            // Check remaining tickets before creating tickets
            $remainingTicketsBefore = $event->remaining_tickets;

            // Create tickets
            for ($i = 0; $i < $quantity; $i++) {
                $ticket = new Ticket();
                $ticket->ticket_purchase_id = $purchase->id;
                $ticket->event_id = $event->id;
                $ticket->user_id = $purchase->user_id;
                $ticket->status = 'confirmed';
                $ticket->ticket_number = $ticket->generateTicketNumber();
                $ticket->price = $event->ticket_price;
                $ticket->booked_at = now();
                $ticket->save();
            }

            // Refresh event to get updated ticket counts
            $event->refresh();
            
            // Check if event just became sold out
            if ($event->remaining_tickets <= 0 && $remainingTicketsBefore > 0) {
                // Event just sold out - notify all admins
                $eventUrl = "/admin/events";
                if ($event->id) {
                    $eventUrl = "/events/{$event->id}";
                }
                
                NotificationService::notifyAllAdmins(
                    'Event Sold Out',
                    "Event '{$event->title}' is now sold out.",
                    'event',
                    [
                        'action_url' => $eventUrl,
                        'action_text' => 'View Event',
                        'metadata' => [
                            'event_id' => $event->id,
                            'event_title' => $event->title,
                        ]
                    ]
                );
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
     * Create cancelled tickets for a failed purchase
     */
    private function createCancelledTicketsForPurchase(TicketPurchase $purchase): void
    {
        try {
            // Check if tickets already exist to prevent duplicates
            $existingTicketsCount = Ticket::where('ticket_purchase_id', $purchase->id)->count();
            if ($existingTicketsCount > 0) {
                return; // Tickets already exist, skip creation
            }

            $event = $purchase->event;
            if (!$event || !$event->ticket_price) {
                return;
            }

            $quantity = (int) ($purchase->total / $event->ticket_price);

            // Create cancelled tickets
            for ($i = 0; $i < $quantity; $i++) {
                $ticket = new Ticket();
                $ticket->ticket_purchase_id = $purchase->id;
                $ticket->event_id = $event->id;
                $ticket->user_id = $purchase->user_id;
                $ticket->status = 'failed';
                $ticket->ticket_number = $ticket->generateTicketNumber();
                $ticket->price = $event->ticket_price;
                $ticket->booked_at = now();
                $ticket->save();
            }

            Log::info('Created failed tickets for failed purchase', [
                'purchase_id' => $purchase->id,
                'quantity' => $quantity,
                'event_id' => $event->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create cancelled tickets for purchase', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Decrement product stock for a purchase
     */
    private function decrementProductStock(ProductPurchase $purchase): void
    {
        try {
            $purchaseItems = $purchase->productPurchaseItems;
            
            if ($purchaseItems->isEmpty()) {
                return;
            }

            foreach ($purchaseItems as $item) {
                if (!$item->variant) {
                    // Note: Currently all purchase items require variants (foreign key constraint)
                    // If products without variants are supported in the future, handle here
                    continue;
                }

                $variant = $item->variant;
                $oldStock = $variant->stock;
                $success = $variant->decrementStock($item->quantity);

                if (!$success) {
                    throw new \Exception("Insufficient stock for variant {$variant->id}");
                }

                // Check if variant is now out of stock
                $variant->refresh(); // Refresh to get updated stock value
                if ($variant->stock <= 0 && $oldStock > 0) {
                    // Variant just went out of stock - notify all admins
                    $product = $variant->product;
                    if ($product) {
                        $variantDetails = [];
                        if ($variant->color) {
                            $variantDetails[] = $variant->color->name;
                        }
                        if ($variant->size) {
                            $variantDetails[] = $variant->size->name;
                        }
                        $variantInfo = !empty($variantDetails) ? ' (' . implode(', ', $variantDetails) . ')' : '';
                        
                        $productUrl = '/admin/products';
                        if ($product->subcategory && $product->subcategory->category) {
                            $productUrl = "/shop/{$product->subcategory->category->slug}/{$product->subcategory->slug}/{$product->slug}";
                        }
                        
                        \App\Services\NotificationService::notifyAllAdmins(
                            'Product Out of Stock',
                            "Product '{$product->name}'{$variantInfo} is now out of stock.",
                            'system',
                            [
                                'action_url' => $productUrl,
                                'action_text' => 'View Product',
                                'metadata' => [
                                    'product_id' => $product->id,
                                    'variant_id' => $variant->id,
                                    'product_name' => $product->name,
                                ]
                            ]
                        );
                    }
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
    private function sendProductConfirmationEmail(ProductPurchase $purchase): void
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
    private function sendTicketConfirmationEmail(TicketPurchase $purchase): void
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
     * Create notifications for product orders
     */
    private function createProductOrderNotifications(ProductPurchase $purchase): void
    {
        try {
            $user = $purchase->user;
            if (!$user) return;

            $shippingAmount = optional($purchase->shipping_address)['shipping_amount'] ?? 0;

            // Customer notifications
            NotificationService::createPaymentNotification(
                $user,
                'Payment Successful',
                'Your payment of ₱' . number_format($purchase->total - $shippingAmount, 2) . ' has been processed successfully.',
                [
                    'action_url' => '/my-orders',
                    'action_text' => 'View Orders'
                ]
            );

            NotificationService::createOrderNotification(
                $user,
                'Order Confirmed',
                'Your order #' . $purchase->id . ' has been confirmed and is being prepared for shipment.',
                [
                    'action_url' => '/my-orders/' . $purchase->id,
                    'action_text' => 'Track Order'
                ]
            );

            // Admin notifications - notify all admin users
            $adminUsers = User::where('role', 'admin')->get();
            foreach ($adminUsers as $admin) {
                NotificationService::createOrderNotification(
                    $admin,
                    'New Order Received',
                    'A new order #' . $purchase->id . ' has been placed by ' . $user->user_name . ' for ₱' . number_format($purchase->total, 2) . ' worth of products.',
                    [
                        'action_url' => '/admin/orders/' . $purchase->id,
                        'action_text' => 'View Order'
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to create product order notifications', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create notifications for event orders
     */
    private function createEventOrderNotifications(TicketPurchase $purchase): void
    {
        try {
            $user = $purchase->user;
            if (!$user) return;

            $event = $purchase->event;

            // Customer notifications
            NotificationService::createPaymentNotification(
                $user,
                'Payment Successful',
                'Your payment of ₱' . number_format($purchase->total, 2) . ' has been processed successfully.',
                [
                    'action_url' => '/my-tickets',
                    'action_text' => 'View Tickets'
                ]
            );

            // Event registration confirmed notification
            if ($event) {
                $ticketCount = $purchase->tickets()->count();
                
                NotificationService::createEventNotification(
                    $user,
                    'Event Registration Confirmed',
                    'You have successfully registered for "' . $event->title . '" event. Your ticket has been sent to your email.',
                    [
                        'action_url' => '/my-tickets',
                        'action_text' => 'View Tickets'
                    ]
                );

                // Admin notifications - notify all admin users
                $adminUsers = User::where('role', 'admin')->get();
                foreach ($adminUsers as $admin) {
                    NotificationService::createEventNotification(
                        $admin,
                        'New Event Registration',
                        $user->user_name . ' has registered for "' . $event->title . '" event. ' . $ticketCount . ' ticket(s) purchased for ₱' . number_format($purchase->total, 2) . '.',
                        [
                            'action_url' => '/admin/events/' . $event->id,
                            'action_text' => 'View Event'
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to create event order notifications', [
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