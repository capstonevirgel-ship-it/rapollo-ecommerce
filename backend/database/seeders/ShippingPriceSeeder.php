<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ShippingPrice;

class ShippingPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shippingPrices = [
            [
                'region' => 'local',
                'price' => 50.00,
                'description' => 'Same city delivery within 1-2 business days',
                'is_active' => true
            ],
            [
                'region' => 'luzon',
                'price' => 150.00,
                'description' => 'Delivery to Luzon provinces within 2-3 business days',
                'is_active' => true
            ],
            [
                'region' => 'cebu',
                'price' => 100.00,
                'description' => 'Within Cebu province delivery within 1-2 business days',
                'is_active' => true
            ],
            [
                'region' => 'visayas',
                'price' => 200.00,
                'description' => 'Delivery to Visayas region within 3-5 business days',
                'is_active' => true
            ],
            [
                'region' => 'mindanao',
                'price' => 250.00,
                'description' => 'Delivery to Mindanao region within 5-7 business days',
                'is_active' => true
            ]
        ];

        foreach ($shippingPrices as $price) {
            ShippingPrice::updateOrCreate(
                ['region' => $price['region']],
                $price
            );
        }
    }
}
