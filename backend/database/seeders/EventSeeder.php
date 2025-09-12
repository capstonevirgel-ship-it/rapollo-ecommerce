<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create an admin user
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::create([
                'user_name' => 'admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'role' => 'admin'
            ]);
        }

        // Create sample rap battle events
        $events = [
            [
                'admin_id' => $admin->id,
                'title' => 'Fliptop Battle League - Manila Championship',
                'description' => 'The biggest rap battle event in the Philippines! Watch the country\'s top emcees battle it out for the championship title. Featuring surprise guest performances and exclusive merchandise.',
                'date' => Carbon::now()->addDays(30)->format('Y-m-d'),
                'location' => 'Araneta Coliseum, Quezon City',
                'poster_url' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=800&h=600&fit=crop',
                'ticket_price' => 500.00,
                'max_tickets' => 5000,
                'available_tickets' => 5000
            ],
            [
                'admin_id' => $admin->id,
                'title' => 'Sunugan Rap Battle - Cebu Edition',
                'description' => 'Experience the raw energy of Cebu\'s rap battle scene. Local emcees will showcase their skills in this intense competition. Food stalls and merchandise available.',
                'date' => Carbon::now()->addDays(45)->format('Y-m-d'),
                'location' => 'Waterfront Cebu City Hotel, Cebu',
                'poster_url' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=600&fit=crop',
                'ticket_price' => 300.00,
                'max_tickets' => 800,
                'available_tickets' => 800
            ],
            [
                'admin_id' => $admin->id,
                'title' => 'Underground Rap Battle - Makati',
                'description' => 'An intimate underground rap battle featuring up-and-coming artists. Discover new talent and enjoy the authentic hip-hop culture. Limited seating available.',
                'date' => Carbon::now()->addDays(15)->format('Y-m-d'),
                'location' => 'The Circuit, Makati City',
                'poster_url' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800&h=600&fit=crop',
                'ticket_price' => 200.00,
                'max_tickets' => 200,
                'available_tickets' => 200
            ],
            [
                'admin_id' => $admin->id,
                'title' => 'Rap Battle Workshop & Competition',
                'description' => 'Learn the art of rap battling from professional emcees, then showcase your skills in a friendly competition. Perfect for beginners and aspiring rappers.',
                'date' => Carbon::now()->addDays(20)->format('Y-m-d'),
                'location' => 'Cultural Center of the Philippines, Manila',
                'poster_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&h=600&fit=crop',
                'ticket_price' => 150.00,
                'max_tickets' => 100,
                'available_tickets' => 100
            ],
            [
                'admin_id' => $admin->id,
                'title' => 'Battle of the Beats - Davao',
                'description' => 'A unique rap battle event combining freestyle rap with live beat production. Watch emcees battle while producers create beats on the spot. Food and drinks available.',
                'date' => Carbon::now()->addDays(10)->format('Y-m-d'),
                'location' => 'SMX Convention Center, Davao City',
                'poster_url' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=800&h=600&fit=crop',
                'ticket_price' => 250.00,
                'max_tickets' => 300,
                'available_tickets' => 300
            ]
        ];

        foreach ($events as $eventData) {
            Event::create($eventData);
        }
    }
}
