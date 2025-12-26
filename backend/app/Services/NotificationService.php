<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notification to WebSocket server
     */
    private static function broadcastToWebSocket(int $userId, array $notificationData): void
    {
        $websocketUrl = env('WEBSOCKET_SERVER_URL', 'http://localhost:6001');
        
        try {
            $response = Http::timeout(2)->post("{$websocketUrl}/notify", [
                'userId' => $userId,
                'notification' => $notificationData
            ]);

            if (!$response->successful()) {
                Log::warning('Failed to broadcast notification to WebSocket server', [
                    'userId' => $userId,
                    'status' => $response->status()
                ]);
            }
        } catch (\Exception $e) {
            // Silently fail if WebSocket server is not available
            // This ensures notifications are still created even if WebSocket is down
            Log::debug('WebSocket server not available', [
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create notification and broadcast to WebSocket
     */
    private static function createAndBroadcast(User $user, string $title, string $message, string $type, array $options = []): void
    {
        $notification = Notification::createForUser(
            $user->id,
            $title,
            $message,
            $type,
            $options
        );

        // Broadcast to WebSocket server
        self::broadcastToWebSocket($user->id, [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'type' => $notification->type,
            'read' => $notification->read,
            'created_at' => $notification->created_at->toISOString(),
            'action_url' => $notification->action_url,
            'action_text' => $notification->action_text,
            'metadata' => $notification->metadata,
        ]);
    }
    public static function createOrderNotification(User $user, string $title, string $message, array $options = []): void
    {
        self::createAndBroadcast($user, $title, $message, 'order', $options);
    }

    public static function createPaymentNotification(User $user, string $title, string $message, array $options = []): void
    {
        self::createAndBroadcast($user, $title, $message, 'payment', $options);
    }

    public static function createEventNotification(User $user, string $title, string $message, array $options = []): void
    {
        self::createAndBroadcast($user, $title, $message, 'event', $options);
    }

    public static function createSystemNotification(User $user, string $title, string $message, array $options = []): void
    {
        self::createAndBroadcast($user, $title, $message, 'system', $options);
    }

    public static function createPromotionNotification(User $user, string $title, string $message, array $options = []): void
    {
        self::createAndBroadcast($user, $title, $message, 'promotion', $options);
    }

    public static function createSuspensionNotification(User $user, string $reason, bool $isSuspended): void
    {
        if ($isSuspended) {
            $title = 'Account Suspended';
            $message = "Your account has been suspended. Reason: {$reason}. Please contact support if you believe this is an error.";
        } else {
            $title = 'Account Suspension Lifted';
            $message = 'Your account suspension has been lifted. You can now proceed with purchases.';
        }

        self::createAndBroadcast(
            $user,
            $title,
            $message,
            'system',
            [
                'action_url' => '/profile',
                'action_text' => 'View Profile'
            ]
        );
    }

    /**
     * Notify all admin users about an event (e.g., out of stock, sold out)
     */
    public static function notifyAllAdmins(string $title, string $message, string $type, array $options = []): void
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            self::createAndBroadcast($admin, $title, $message, $type, $options);
        }
    }

    /**
     * Broadcast count update to all admin users via WebSocket
     */
    public static function broadcastCountUpdate(string $type, int $count): void
    {
        $websocketUrl = env('WEBSOCKET_SERVER_URL', 'http://localhost:6001');
        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            try {
                $response = Http::timeout(2)->post("{$websocketUrl}/notify-count", [
                    'userId' => $admin->id,
                    'type' => $type,
                    'count' => $count
                ]);

                if (!$response->successful()) {
                    Log::warning('Failed to broadcast count update to WebSocket server', [
                        'userId' => $admin->id,
                        'type' => $type,
                        'status' => $response->status()
                    ]);
                }
            } catch (\Exception $e) {
                Log::debug('WebSocket server not available for count update', [
                    'userId' => $admin->id,
                    'type' => $type,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
