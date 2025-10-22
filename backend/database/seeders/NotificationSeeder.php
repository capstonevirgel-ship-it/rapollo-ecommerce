<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->info('No users found. Please run UserSeeder first.');
            return;
        }

        $sampleNotifications = [
            [
                'title' => 'Order Confirmed',
                'message' => 'Your order #12345 has been confirmed and is being prepared for shipment.',
                'type' => 'order',
                'read' => false,
                'action_url' => '/my-orders/12345',
                'action_text' => 'Track Order'
            ],
            [
                'title' => 'Payment Successful',
                'message' => 'Your payment of ₱2,500.00 has been processed successfully.',
                'type' => 'payment',
                'read' => false,
                'action_url' => '/my-orders',
                'action_text' => 'View Orders'
            ],
            [
                'title' => 'Order Shipped',
                'message' => 'Your order #12344 has been shipped! Track your package with tracking number: TRK123456789.',
                'type' => 'order',
                'read' => true,
                'action_url' => '/my-orders/12344',
                'action_text' => 'Track Package'
            ],
            [
                'title' => 'Event Registration Confirmed',
                'message' => 'You have successfully registered for "Fashion Week 2025" event. Your ticket has been sent to your email.',
                'type' => 'event',
                'read' => true,
                'action_url' => '/my-tickets',
                'action_text' => 'View Tickets'
            ],
            [
                'title' => 'Special Offer Available',
                'message' => 'Get 20% off on all summer collection items! Use code SUMMER20 at checkout. Valid until January 15th.',
                'type' => 'promotion',
                'read' => false,
                'action_url' => '/shop?promo=summer20',
                'action_text' => 'Shop Now'
            ],
            [
                'title' => 'Account Security Alert',
                'message' => 'We noticed a new login from a different device. If this wasn\'t you, please secure your account.',
                'type' => 'system',
                'read' => true,
                'action_url' => '/profile/security',
                'action_text' => 'Review Security'
            ]
        ];

        foreach ($users as $user) {
            // Create 3-5 random notifications for each user
            $notificationCount = rand(3, 5);
            $selectedNotifications = array_rand($sampleNotifications, $notificationCount);
            
            if (!is_array($selectedNotifications)) {
                $selectedNotifications = [$selectedNotifications];
            }
            
            foreach ($selectedNotifications as $index) {
                $notification = $sampleNotifications[$index];
                Notification::create([
                    'user_id' => $user->id,
                    'title' => $notification['title'],
                    'message' => $notification['message'],
                    'type' => $notification['type'],
                    'read' => $notification['read'],
                    'action_url' => $notification['action_url'],
                    'action_text' => $notification['action_text'],
                    'created_at' => now()->subDays(rand(1, 7))->subHours(rand(0, 23)),
                ]);
            }
        }

        $this->command->info('Sample notifications created successfully!');
    }
}
