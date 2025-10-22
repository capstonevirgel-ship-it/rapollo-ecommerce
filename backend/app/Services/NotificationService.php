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
}
