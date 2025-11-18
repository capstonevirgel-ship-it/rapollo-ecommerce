<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayMongoService
{
    private $baseUrl;
    private $publicKey;
    private $secretKey;
    private $webhookSecret;

    public function __construct()
    {
        $this->baseUrl = 'https://api.paymongo.com/v1';
        $this->publicKey = config('services.paymongo.public_key');
        $this->secretKey = config('services.paymongo.secret_key');
        $this->webhookSecret = config('services.paymongo.webhook_secret');
    }

    /**
     * Create a payment intent
     */
    public function createPaymentIntent($amount, $currency = 'PHP', $metadata = [])
    {
        try {
            // Convert amount to integer (centavos)
            $amountInCentavos = (int) round($amount * 100);
            
            // Flatten metadata to avoid nested objects
            $flattenedMetadata = [];
            foreach ($metadata as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $flattenedMetadata[$key] = json_encode($value);
                } else {
                    $flattenedMetadata[$key] = (string) $value;
                }
            }
            
            $response = Http::withBasicAuth($this->secretKey, '')
                ->post("{$this->baseUrl}/payment_intents", [
                    'data' => [
                        'attributes' => [
                            'amount' => $amountInCentavos,
                            'currency' => $currency,
                            'metadata' => $flattenedMetadata,
                            'payment_method_allowed' => [
                                'card',
                                'paymaya',
                                'gcash',
                                'grab_pay'
                            ]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('PayMongo Payment Intent Creation Failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('PayMongo Payment Intent Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Create a checkout session for hosted payment page
     */
    public function createCheckoutSession($amount, $currency = 'PHP', $metadata = [], $successUrl = null, $cancelUrl = null, $lineItems = null)
    {
        try {
            // Convert amount to integer (centavos)
            $amountInCentavos = (int) round($amount * 100);
            
            // Flatten metadata to avoid nested objects
            $flattenedMetadata = [];
            foreach ($metadata as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $flattenedMetadata[$key] = json_encode($value);
                } else {
                    $flattenedMetadata[$key] = (string) $value;
                }
            }
            
            // Default line items if not provided
            if (!$lineItems) {
                $lineItems = [
                    [
                        'name' => 'Order Payment',
                        'description' => 'Payment for order',
                        'amount' => $amountInCentavos,
                        'currency' => $currency,
                        'quantity' => 1
                    ]
                ];
            }
            
            $attributes = [
                'line_items' => $lineItems,
                'payment_method_types' => [
                    'card',
                    'paymaya',
                    'gcash',
                    'grab_pay'
                ],
                'success_url' => $successUrl,
                'metadata' => $flattenedMetadata
            ];
            
            // Only include cancel_url if provided
            if ($cancelUrl !== null) {
                $attributes['cancel_url'] = $cancelUrl;
            }
            
            $response = Http::withBasicAuth($this->secretKey, '')
                ->post("{$this->baseUrl}/checkout_sessions", [
                    'data' => [
                        'attributes' => $attributes
                    ]
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('PayMongo Checkout Session Creation Failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('PayMongo Checkout Session Creation Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Attach payment method to payment intent
     */
    public function attachPaymentMethod($paymentIntentId, $paymentMethodId)
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->post("{$this->baseUrl}/payment_intents/{$paymentIntentId}/attach", [
                    'data' => [
                        'attributes' => [
                            'payment_method' => $paymentMethodId
                        ]
                    ]
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('PayMongo Payment Method Attachment Failed', [
                'payment_intent_id' => $paymentIntentId,
                'payment_method_id' => $paymentMethodId,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('PayMongo Payment Method Attachment Exception', [
                'payment_intent_id' => $paymentIntentId,
                'payment_method_id' => $paymentMethodId,
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Create a payment method
     */
    public function createPaymentMethod($type, $details)
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->post("{$this->baseUrl}/payment_methods", [
                    'data' => [
                        'attributes' => [
                            'type' => $type,
                            'details' => $details
                        ]
                    ]
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('PayMongo Payment Method Creation Failed', [
                'type' => $type,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('PayMongo Payment Method Creation Exception', [
                'type' => $type,
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Retrieve payment intent
     */
    public function getPaymentIntent($paymentIntentId)
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get("{$this->baseUrl}/payment_intents/{$paymentIntentId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('PayMongo Payment Intent Retrieval Failed', [
                'payment_intent_id' => $paymentIntentId,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('PayMongo Payment Intent Retrieval Exception', [
                'payment_intent_id' => $paymentIntentId,
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Create a refund
     */
    public function createRefund($paymentId, $amount, $reason = 'requested_by_customer')
    {
        try {
            // Convert amount to integer (centavos)
            $amountInCentavos = (int) round($amount * 100);
            
            $response = Http::withBasicAuth($this->secretKey, '')
                ->post("{$this->baseUrl}/refunds", [
                    'data' => [
                        'attributes' => [
                            'amount' => $amountInCentavos,
                            'payment_id' => $paymentId,
                            'reason' => $reason
                        ]
                    ]
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('PayMongo Refund Creation Failed', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('PayMongo Refund Creation Exception', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($payload, $signature)
    {
        if (!$signature) {
            return false;
        }

        // Parse PayMongo signature format: t=timestamp,te=signature,li=livemode
        $signatureParts = [];
        parse_str(str_replace(',', '&', $signature), $signatureParts);
        
        $timestamp = $signatureParts['t'] ?? null;
        $expectedSignature = $signatureParts['te'] ?? null;
        
        if (!$timestamp || !$expectedSignature) {
            return false;
        }

        // Create expected signature
        $signedPayload = $timestamp . '.' . $payload;
        $computedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);
        
        return hash_equals($expectedSignature, $computedSignature);
    }

    /**
     * Get public key for frontend
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Get client secret for payment intent
     */
    public function getClientSecret($paymentIntentId)
    {
        $paymentIntent = $this->getPaymentIntent($paymentIntentId);
        
        if ($paymentIntent && isset($paymentIntent['data']['attributes']['client_key'])) {
            return $paymentIntent['data']['attributes']['client_key'];
        }

        return null;
    }
}
