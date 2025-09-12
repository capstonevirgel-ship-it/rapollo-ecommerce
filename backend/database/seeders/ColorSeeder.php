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
            ['name' => 'Navy', 'hex_code' => '#000080'],
            ['name' => 'Brown', 'hex_code' => '#A52A2A'],
            ['name' => 'Beige', 'hex_code' => '#F5F5DC'],
            ['name' => 'Khaki', 'hex_code' => '#F0E68C'],
            ['name' => 'Maroon', 'hex_code' => '#800000']
        ];

        foreach ($colors as $color) {
            Color::create($color);
        }
    }
}