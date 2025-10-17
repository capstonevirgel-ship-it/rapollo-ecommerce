<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default user
        $user = User::create([
            'user_name' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // Create profile for user
        Profile::create([
            'user_id' => $user->id,
            'full_name' => 'Test User',
            'phone' => '+1234567890',
            'address' => '123 Test Street, Test City, TC 12345',
            'avatar_url' => null,
        ]);

        // Create admin user
        User::create([
            'user_name' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'), // change as needed
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Run seeders in correct order
        $this->call([
            BrandSeeder::class,
            ColorSeeder::class,
            SizeSeeder::class,
            CategorySeeder::class,
            SubcategorySeeder::class,
            ProductSeeder::class,
            ProductVariantSeeder::class,
            ProductImageSeeder::class,
            EventSeeder::class,
        ]);
    }
}
