<?php

namespace App\Services;

use App\Models\User;
use App\Models\ProductPurchase;
use App\Models\TicketPurchase;
use App\Services\NotificationService;

class UserSuspensionService
{
    /**
     * Get the total cancellation count for a user.
     *
     * @param  User  $user
     * @return int
     */
    public static function getCancellationCount(User $user): int
    {
        // Count cancelled product purchases
        $productCancellations = ProductPurchase::where('user_id', $user->id)
            ->where('status', 'cancelled')
            ->count();

        // Count cancelled tickets (not purchases, but individual tickets)
        $ticketCancellations = \App\Models\Ticket::where('user_id', $user->id)
            ->where('status', 'cancelled')
            ->count();

        return $productCancellations + $ticketCancellations;
    }

    /**
     * Check if user should be suspended based on cancellation count and suspend if needed.
     *
     * @param  User  $user
     * @return bool True if user was suspended, false otherwise
     */
    public static function checkAndSuspendIfNeeded(User $user): bool
    {
        // Don't suspend if already suspended
        if ($user->isSuspended()) {
            return false;
        }

        $cancellationCount = self::getCancellationCount($user);

        // Auto-suspend if cancellation count reaches 3
        if ($cancellationCount >= 3) {
            $reason = "Automatic suspension due to {$cancellationCount} purchase cancellations. Please contact support.";
            
            $user->suspend($reason);
            
            // Send notification
            NotificationService::createSuspensionNotification(
                $user,
                $reason,
                true
            );

            return true;
        }

        return false;
    }
}
