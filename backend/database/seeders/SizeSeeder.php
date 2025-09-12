<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Size;

class SizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sizes = [
            // Clothing sizes
            ['name' => 'XS', 'description' => 'Extra Small'],
            ['name' => 'S', 'description' => 'Small'],
            ['name' => 'M', 'description' => 'Medium'],
            ['name' => 'L', 'description' => 'Large'],
            ['name' => 'XL', 'description' => 'Extra Large'],
            ['name' => 'XXL', 'description' => 'Double Extra Large'],
            ['name' => 'XXXL', 'description' => 'Triple Extra Large'],
            
            // Shoe sizes
            ['name' => '6', 'description' => 'US Size 6'],
            ['name' => '6.5', 'description' => 'US Size 6.5'],
            ['name' => '7', 'description' => 'US Size 7'],
            ['name' => '7.5', 'description' => 'US Size 7.5'],
            ['name' => '8', 'description' => 'US Size 8'],
            ['name' => '8.5', 'description' => 'US Size 8.5'],
            ['name' => '9', 'description' => 'US Size 9'],
            ['name' => '9.5', 'description' => 'US Size 9.5'],
            ['name' => '10', 'description' => 'US Size 10'],
            ['name' => '10.5', 'description' => 'US Size 10.5'],
            ['name' => '11', 'description' => 'US Size 11'],
            ['name' => '11.5', 'description' => 'US Size 11.5'],
            ['name' => '12', 'description' => 'US Size 12'],
            
            // Accessory sizes
            ['name' => 'One Size', 'description' => 'One Size Fits All'],
            ['name' => 'Adjustable', 'description' => 'Adjustable Size']
        ];

        foreach ($sizes as $size) {
            Size::create($size);
        }
    }
}