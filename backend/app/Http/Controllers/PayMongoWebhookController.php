<?php

namespace App\Http\Controllers;

use App\Services\PayMongoWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PayMongoWebhookController extends Controller
{
    protected $webhookService;

    public function __construct(PayMongoWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle PayMongo webhook events
     */
    public function handle(Request $request)
    {
        try {
            // Verify webhook signature
            if (!$this->verifyWebhookSignature($request)) {
                Log::warning('PayMongo webhook: Invalid signature', [
                    'headers' => $request->headers->all(),
                    'body' => $request->getContent()
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $payload = $request->json()->all();
            $eventType = $payload['data']['type'] ?? null;

            Log::info('PayMongo webhook received', [
                'event_type' => $eventType,
                'payload' => $payload
            ]);

            // Process the webhook event
            $result = $this->webhookService->processEvent($payload);

            if ($result['success']) {
                return response()->json(['status' => 'success'], 200);
            } else {
                Log::error('PayMongo webhook processing failed', [
                    'error' => $result['error'],
                    'payload' => $payload
                ]);
                return response()->json(['error' => $result['error']], 400);
            }

        } catch (\Exception $e) {
            Log::error('PayMongo webhook error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->getContent()
            ]);
            
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Verify webhook signature from PayMongo
     */
    private function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('Paymongo-Signature');
        $webhookSecret = config('services.paymongo.webhook_secret');
        
        if (!$signature || !$webhookSecret) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        
        return hash_equals($expectedSignature, $signature);
    }
}
