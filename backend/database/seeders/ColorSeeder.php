<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Color;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = [
            ['name' => 'Black', 'hex_code' => '#000000'],
            ['name' => 'White', 'hex_code' => '#FFFFFF'],
            ['name' => 'Red', 'hex_code' => '#FF0000'],
            ['name' => 'Blue', 'hex_code' => '#0000FF'],
            ['name' => 'Green', 'hex_code' => '#008000'],
            ['name' => 'Yellow', 'hex_code' => '#FFFF00'],
            ['name' => 'Orange', 'hex_code' => '#FFA500'],
            ['name' => 'Purple', 'hex_code' => '#800080'],
            ['name' => 'Pink', 'hex_code' => '#FFC0CB'],
            ['name' => 'Gray', 'hex_code' => '#808080'],
            ['name' => 'Grey', 'hex_code' => '#808080'], // Alternative spelling
            ['name' => 'Silver', 'hex_code' => '#C0C0C0'],
            ['name' => 'Gold', 'hex_code' => '#FFD700'],
            ['name' => 'Navy', 'hex_code' => '#000080'],
            ['name' => 'Brown', 'hex_code' => '#A52A2A'],
            ['name' => 'Beige', 'hex_code' => '#F5F5DC'],
            ['name' => 'Khaki', 'hex_code' => '#F0E68C'],
            ['name' => 'Maroon', 'hex_code' => '#800000'],
            ['name' => 'Cyan', 'hex_code' => '#00FFFF'],
            ['name' => 'Magenta', 'hex_code' => '#FF00FF'],
            ['name' => 'Lime', 'hex_code' => '#00FF00'],
            ['name' => 'Teal', 'hex_code' => '#008080'],
            ['name' => 'Olive', 'hex_code' => '#808000'],
            ['name' => 'Coral', 'hex_code' => '#FF7F50'],
            ['name' => 'Turquoise', 'hex_code' => '#40E0D0'],
            ['name' => 'Indigo', 'hex_code' => '#4B0082'],
            ['name' => 'Violet', 'hex_code' => '#8B00FF'],
            ['name' => 'Tan', 'hex_code' => '#D2B48C'],
            ['name' => 'Burgundy', 'hex_code' => '#800020'],
            ['name' => 'Charcoal', 'hex_code' => '#36454F']
        ];

        foreach ($colors as $color) {
            // Use updateOrCreate to avoid duplicates if seeder is run multiple times
            Color::updateOrCreate(
                ['name' => $color['name']],
                ['hex_code' => $color['hex_code']]
            );
        }
    }
}