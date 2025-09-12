<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Men\'s Clothing',
                'slug' => 'mens-clothing',
                'meta_title' => 'Men\'s Clothing - Fashion & Style',
                'meta_description' => 'Shop the latest men\'s clothing including shirts, pants, jackets, and accessories.'
            ],
            [
                'name' => 'Women\'s Clothing',
                'slug' => 'womens-clothing',
                'meta_title' => 'Women\'s Clothing - Fashion & Style',
                'meta_description' => 'Discover women\'s fashion including dresses, tops, bottoms, and accessories.'
            ],
            [
                'name' => 'Shoes',
                'slug' => 'shoes',
                'meta_title' => 'Shoes - Athletic & Casual Footwear',
                'meta_description' => 'Find the perfect shoes for every occasion - athletic, casual, and formal footwear.'
            ],
            [
                'name' => 'Accessories',
                'slug' => 'accessories',
                'meta_title' => 'Accessories - Bags, Watches & More',
                'meta_description' => 'Complete your look with our selection of bags, watches, hats, and other accessories.'
            ],
            [
                'name' => 'Sports & Outdoors',
                'slug' => 'sports-outdoors',
                'meta_title' => 'Sports & Outdoor Gear',
                'meta_description' => 'Gear up for your next adventure with our sports and outdoor equipment.'
            ],
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'meta_title' => 'Electronics & Gadgets',
                'meta_description' => 'Latest electronics, gadgets, and tech accessories for modern living.'
            ]
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}