<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public static function createOrderNotification(User $user, string $title, string $message, array $options = []): void
    {
        Notification::createForUser(
            $user->id,
            $title,
            $message,
            'order',
            $options
        );
    }

    public static function createPaymentNotification(User $user, string $title, string $message, array $options = []): void
    {
        Notification::createForUser(
            $user->id,
            $title,
            $message,
            'payment',
            $options
        );
    }

    public static function createEventNotification(User $user, string $title, string $message, array $options = []): void
    {
        Notification::createForUser(
            $user->id,
            $title,
            $message,
            'event',
            $options
        );
    }

    public static function createSystemNotification(User $user, string $title, string $message, array $options = []): void
    {
        Notification::createForUser(
            $user->id,
            $title,
            $message,
            'system',
            $options
        );
    }

    public static function createPromotionNotification(User $user, string $title, string $message, array $options = []): void
    {
        Notification::createForUser(
            $user->id,
            $title,
            $message,
            'promotion',
            $options
        );
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

        Notification::createForUser(
            $user->id,
            $title,
            $message,
            'system',
            [
                'action_url' => '/profile',
                'action_text' => 'View Profile'
            ]
        );
    }
}
