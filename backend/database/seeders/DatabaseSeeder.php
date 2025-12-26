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
            'user_name' => 'virgel24',
            'email' => '24virgel@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'email_verified_at' => now(),
            'is_suspended' => false,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        // Create profile for user
        Profile::create([
            'user_id' => $user->id,
            'full_name' => 'Virgel Galleto',
            'phone' => '+1234567890',
            'street' => 'n/a',
            'barangay' => 'Poblacion',
            'city' => 'Cordova',
            'province' => 'Cebu',
            'zipcode' => '6000',
            'complete_address' => 'n/a, Poblacion, Cordova, Cebu, 6000',
            'country' => 'Philippines',
            'avatar_url' => null,
        ]);

        // Create admin user
        User::create([
            'user_name' => 'admin',
            'email' => 'capstonevirgel@gmail.com',
            'password' => Hash::make('admin123'), // change as needed
            'role' => 'admin',
            'email_verified_at' => now(),
            'is_suspended' => false,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        // Run seeders in correct order
        $this->call([
            SettingsSeeder::class,
            BrandSeeder::class,
            ColorSeeder::class,
            SizeSeeder::class,
            CategorySeeder::class,
            SubcategorySeeder::class,
            TaxPriceSeeder::class,
            ProductSeeder::class,
            ProductVariantSeeder::class,
            ProductSizeSeeder::class,
            ProductImageSeeder::class,
            EventSeeder::class,
            ShippingPriceSeeder::class,
        ]);
    }
}
