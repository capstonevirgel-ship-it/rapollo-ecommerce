<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Subcategory;
use App\Models\Category;
use Illuminate\Support\Str;

class SubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mensClothing = Category::where('slug', 'mens-clothing')->first();
        $womensClothing = Category::where('slug', 'womens-clothing')->first();
        $shoes = Category::where('slug', 'shoes')->first();
        $accessories = Category::where('slug', 'accessories')->first();
        $sports = Category::where('slug', 'sports-outdoors')->first();
        $electronics = Category::where('slug', 'electronics')->first();

        $subcategories = [
            // Men's Clothing
            ['category_id' => $mensClothing->id, 'name' => 'T-Shirts', 'slug' => 'mens-t-shirts', 'meta_title' => 'Men\'s T-Shirts', 'meta_description' => 'Comfortable and stylish men\'s t-shirts in various colors and fits.'],
            ['category_id' => $mensClothing->id, 'name' => 'Shirts', 'slug' => 'mens-shirts', 'meta_title' => 'Men\'s Shirts', 'meta_description' => 'Dress shirts, casual shirts, and button-ups for men.'],
            ['category_id' => $mensClothing->id, 'name' => 'Pants', 'slug' => 'mens-pants', 'meta_title' => 'Men\'s Pants', 'meta_description' => 'Jeans, chinos, dress pants, and casual pants for men.'],
            ['category_id' => $mensClothing->id, 'name' => 'Shorts', 'slug' => 'mens-shorts', 'meta_title' => 'Men\'s Shorts', 'meta_description' => 'Casual and athletic shorts for men.'],
            ['category_id' => $mensClothing->id, 'name' => 'Jackets', 'slug' => 'mens-jackets', 'meta_title' => 'Men\'s Jackets', 'meta_description' => 'Blazers, hoodies, and outerwear for men.'],

            // Women's Clothing
            ['category_id' => $womensClothing->id, 'name' => 'Dresses', 'slug' => 'womens-dresses', 'meta_title' => 'Women\'s Dresses', 'meta_description' => 'Elegant and casual dresses for every occasion.'],
            ['category_id' => $womensClothing->id, 'name' => 'Tops', 'slug' => 'womens-tops', 'meta_title' => 'Women\'s Tops', 'meta_description' => 'Blouses, t-shirts, and casual tops for women.'],
            ['category_id' => $womensClothing->id, 'name' => 'Bottoms', 'slug' => 'womens-bottoms', 'meta_title' => 'Women\'s Bottoms', 'meta_description' => 'Pants, skirts, and shorts for women.'],
            ['category_id' => $womensClothing->id, 'name' => 'Activewear', 'slug' => 'womens-activewear', 'meta_title' => 'Women\'s Activewear', 'meta_description' => 'Workout clothes and athletic wear for women.'],

            // Shoes
            ['category_id' => $shoes->id, 'name' => 'Sneakers', 'slug' => 'sneakers', 'meta_title' => 'Sneakers & Athletic Shoes', 'meta_description' => 'Comfortable sneakers for sports and casual wear.'],
            ['category_id' => $shoes->id, 'name' => 'Dress Shoes', 'slug' => 'dress-shoes', 'meta_title' => 'Dress Shoes', 'meta_description' => 'Formal and business shoes for professional occasions.'],
            ['category_id' => $shoes->id, 'name' => 'Boots', 'slug' => 'boots', 'meta_title' => 'Boots', 'meta_description' => 'Ankle boots, knee-high boots, and work boots.'],
            ['category_id' => $shoes->id, 'name' => 'Sandals', 'slug' => 'sandals', 'meta_title' => 'Sandals', 'meta_description' => 'Comfortable sandals for summer and casual wear.'],

            // Accessories
            ['category_id' => $accessories->id, 'name' => 'Bags', 'slug' => 'bags', 'meta_title' => 'Bags & Purses', 'meta_description' => 'Handbags, backpacks, and travel bags.'],
            ['category_id' => $accessories->id, 'name' => 'Watches', 'slug' => 'watches', 'meta_title' => 'Watches', 'meta_description' => 'Fashion and sports watches for men and women.'],
            ['category_id' => $accessories->id, 'name' => 'Hats', 'slug' => 'hats', 'meta_title' => 'Hats & Caps', 'meta_description' => 'Baseball caps, beanies, and fashion hats.'],
            ['category_id' => $accessories->id, 'name' => 'Jewelry', 'slug' => 'jewelry', 'meta_title' => 'Jewelry', 'meta_description' => 'Necklaces, bracelets, rings, and earrings.'],

            // Sports & Outdoors
            ['category_id' => $sports->id, 'name' => 'Fitness', 'slug' => 'fitness', 'meta_title' => 'Fitness Equipment', 'meta_description' => 'Gym equipment and fitness accessories.'],
            ['category_id' => $sports->id, 'name' => 'Outdoor Gear', 'slug' => 'outdoor-gear', 'meta_title' => 'Outdoor Gear', 'meta_description' => 'Camping, hiking, and outdoor adventure equipment.'],

            // Electronics
            ['category_id' => $electronics->id, 'name' => 'Wearables', 'slug' => 'wearables', 'meta_title' => 'Wearable Technology', 'meta_description' => 'Smartwatches, fitness trackers, and wearable devices.'],
            ['category_id' => $electronics->id, 'name' => 'Audio', 'slug' => 'audio', 'meta_title' => 'Audio Equipment', 'meta_description' => 'Headphones, speakers, and audio accessories.']
        ];

        foreach ($subcategories as $subcategory) {
            Subcategory::create($subcategory);
        }
    }
}