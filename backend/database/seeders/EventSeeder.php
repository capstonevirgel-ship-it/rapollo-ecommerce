<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\Traits\ImageUploadTrait;

class EventSeeder extends Seeder
{
    use ImageUploadTrait;

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

        // Create rap battle events based on actual images
        $events = [
            [
                'admin_id' => $admin->id,
                'title' => 'Kumunidad Rap Battle',
                'content' => 'Join us for an epic rap battle event featuring the best local emcees. Experience the raw energy and creativity of Filipino rap culture. Food stalls and merchandise available.',
                'date' => Carbon::now()->addDays(30)->format('Y-m-d'),
                'location' => 'Manila Convention Center',
                'poster_filename' => 'jun_14_25_kumunidad_4.jpg',
                'ticket_price' => 500.00,
                'max_tickets' => 1000,
                'available_tickets' => 1000
            ],
            [
                'admin_id' => $admin->id,
                'title' => 'Abri Gana Rap Battle',
                'content' => 'Witness the intensity of underground rap battles as talented artists showcase their lyrical skills. A night of fierce competition and authentic hip-hop culture.',
                'date' => Carbon::now()->addDays(45)->format('Y-m-d'),
                'location' => 'Quezon City Memorial Circle',
                'poster_filename' => 'mar_25_25_abri_gana_5.jpg',
                'ticket_price' => 300.00,
                'max_tickets' => 500,
                'available_tickets' => 500
            ],
            [
                'admin_id' => $admin->id,
                'title' => 'Laglag Bara Rap Battle - November Edition',
                'content' => 'The legendary Laglag Bara series continues! Watch as emcees battle it out with their sharpest bars and most creative flows. Limited seating available.',
                'date' => Carbon::now()->addDays(15)->format('Y-m-d'),
                'location' => 'Cultural Center of the Philippines',
                'poster_filename' => 'nov_13_23_laglag_bara_6.jpg',
                'ticket_price' => 400.00,
                'max_tickets' => 300,
                'available_tickets' => 300
            ],
            [
                'admin_id' => $admin->id,
                'title' => 'Laglag Bara Rap Battle - Anniversary Edition',
                'content' => 'Celebrate another year of Laglag Bara with this special anniversary edition. Featuring returning champions and surprise guest performances.',
                'date' => Carbon::now()->addDays(20)->format('Y-m-d'),
                'location' => 'SM Mall of Asia Arena',
                'poster_filename' => 'nov_19_24_laglag_bara_7.jpg',
                'ticket_price' => 600.00,
                'max_tickets' => 2000,
                'available_tickets' => 2000
            ],
            [
                'admin_id' => $admin->id,
                'title' => 'Laglag Bara Rap Battle - October Showdown',
                'content' => 'The October edition brings fresh talent and seasoned veterans to the stage. Experience the evolution of Filipino rap battle culture.',
                'date' => Carbon::now()->addDays(10)->format('Y-m-d'),
                'location' => 'Araneta Coliseum',
                'poster_filename' => 'oct_22_25_laglag_bara_8.jpg',
                'ticket_price' => 450.00,
                'max_tickets' => 1500,
                'available_tickets' => 1500
            ],
            [
                'admin_id' => $admin->id,
                'title' => 'Amihanan Rap Battle',
                'content' => 'From the north comes Amihanan - a showcase of Northern Luzon\'s finest rap talent. Discover new voices and witness regional rap styles.',
                'date' => Carbon::now()->addDays(25)->format('Y-m-d'),
                'location' => 'Baguio Convention Center',
                'poster_filename' => 'sep_1_25_amihanan_4.jpg',
                'ticket_price' => 350.00,
                'max_tickets' => 800,
                'available_tickets' => 800
            ]
        ];

        foreach ($events as $eventData) {
            // Create slug from title
            $slug = strtolower(str_replace([' ', '-'], '_', $eventData['title']));
            
            // Upload poster and get the relative path
            $posterUrl = $this->uploadEventPoster($slug, $eventData['poster_filename']);
            
            // Remove poster_filename from event data
            unset($eventData['poster_filename']);
            
            // Add the uploaded poster URL
            $eventData['poster_url'] = $posterUrl ?: "images/events/{$eventData['poster_filename']}";

            Event::create($eventData);
        }
    }
}
