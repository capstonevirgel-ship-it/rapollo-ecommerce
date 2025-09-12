<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Subcategory;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nike = Brand::where('slug', 'nike')->first();
        $adidas = Brand::where('slug', 'adidas')->first();
        $puma = Brand::where('slug', 'puma')->first();
        $uniqlo = Brand::where('slug', 'uniqlo')->first();
        $zara = Brand::where('slug', 'zara')->first();
        $hm = Brand::where('slug', 'hm')->first();

        $mensTshirts = Subcategory::where('slug', 'mens-t-shirts')->first();
        $mensShirts = Subcategory::where('slug', 'mens-shirts')->first();
        $mensPants = Subcategory::where('slug', 'mens-pants')->first();
        $womensTops = Subcategory::where('slug', 'womens-tops')->first();
        $sneakers = Subcategory::where('slug', 'sneakers')->first();
        $hats = Subcategory::where('slug', 'hats')->first();
        $watches = Subcategory::where('slug', 'watches')->first();

        $products = [
            [
                'subcategory_id' => $mensTshirts->id,
                'brand_id' => $nike->id,
                'name' => 'Classic White T-Shirt',
                'slug' => 'classic-white-t-shirt',
                'description' => 'A timeless classic white t-shirt made from 100% cotton. Perfect for layering or wearing on its own. Features a comfortable regular fit and pre-shrunk fabric for lasting quality.',
                'meta_title' => 'Classic White T-Shirt - Nike',
                'meta_description' => 'Premium quality white t-shirt from Nike. 100% cotton, regular fit, perfect for everyday wear.',
                'is_active' => true,
                'is_featured' => true,
                'is_hot' => false,
                'is_new' => false
            ],
            [
                'subcategory_id' => $mensTshirts->id,
                'brand_id' => $adidas->id,
                'name' => 'Graphic Print T-Shirt',
                'slug' => 'graphic-print-t-shirt',
                'description' => 'Bold graphic print t-shirt featuring the iconic Adidas logo. Made from soft cotton blend for comfort and style. Perfect for casual outings and sports activities.',
                'meta_title' => 'Graphic Print T-Shirt - Adidas',
                'meta_description' => 'Stylish graphic t-shirt with Adidas branding. Soft cotton blend, comfortable fit.',
                'is_active' => true,
                'is_featured' => true,
                'is_hot' => true,
                'is_new' => false
            ],
            [
                'subcategory_id' => $mensShirts->id,
                'brand_id' => $uniqlo->id,
                'name' => 'Linen Button-Up Shirt',
                'slug' => 'linen-button-up-shirt',
                'description' => 'Breathable linen shirt perfect for warm weather. Features a relaxed fit, button-down collar, and classic styling. Ideal for both casual and smart-casual occasions.',
                'meta_title' => 'Linen Button-Up Shirt - Uniqlo',
                'meta_description' => 'Comfortable linen shirt from Uniqlo. Breathable fabric, relaxed fit, perfect for summer.',
                'is_active' => true,
                'is_featured' => false,
                'is_hot' => false,
                'is_new' => true
            ],
            [
                'subcategory_id' => $mensPants->id,
                'brand_id' => $zara->id,
                'name' => 'Slim Fit Jeans',
                'slug' => 'slim-fit-jeans',
                'description' => 'Modern slim fit jeans crafted from premium denim. Features a comfortable stretch blend, classic five-pocket design, and a versatile dark wash that pairs with everything.',
                'meta_title' => 'Slim Fit Jeans - Zara',
                'meta_description' => 'Stylish slim fit jeans from Zara. Premium denim, comfortable stretch, dark wash.',
                'is_active' => true,
                'is_featured' => true,
                'is_hot' => false,
                'is_new' => false
            ],
            [
                'subcategory_id' => $hats->id,
                'brand_id' => $puma->id,
                'name' => 'Snapback Cap',
                'slug' => 'snapback-cap',
                'description' => 'Classic snapback cap with embroidered Puma logo. Features an adjustable snap closure, curved brim, and structured crown. Perfect for sports and casual wear.',
                'meta_title' => 'Snapback Cap - Puma',
                'meta_description' => 'Classic snapback cap from Puma. Adjustable fit, embroidered logo, perfect for sports.',
                'is_active' => true,
                'is_featured' => false,
                'is_hot' => true,
                'is_new' => false
            ],
            [
                'subcategory_id' => $watches->id,
                'brand_id' => $nike->id,
                'name' => 'Sports Watch',
                'slug' => 'sports-watch',
                'description' => 'High-performance sports watch designed for athletes. Features water resistance, stopwatch function, and durable construction. Perfect for training and everyday use.',
                'meta_title' => 'Sports Watch - Nike',
                'meta_description' => 'Professional sports watch from Nike. Water resistant, stopwatch, durable design.',
                'is_active' => true,
                'is_featured' => true,
                'is_hot' => false,
                'is_new' => true
            ],
            [
                'subcategory_id' => $womensTops->id,
                'brand_id' => $hm->id,
                'name' => 'Casual Blouse',
                'slug' => 'casual-blouse',
                'description' => 'Elegant casual blouse with a relaxed fit and soft fabric. Features a classic collar and button-down design. Perfect for office wear or casual outings.',
                'meta_title' => 'Casual Blouse - H&M',
                'meta_description' => 'Elegant casual blouse from H&M. Relaxed fit, soft fabric, perfect for office.',
                'is_active' => true,
                'is_featured' => false,
                'is_hot' => false,
                'is_new' => true
            ],
            [
                'subcategory_id' => $sneakers->id,
                'brand_id' => $adidas->id,
                'name' => 'Classic Sneakers',
                'slug' => 'classic-sneakers',
                'description' => 'Iconic classic sneakers with timeless design. Features comfortable cushioning, durable rubber sole, and the signature three stripes. Perfect for everyday wear.',
                'meta_title' => 'Classic Sneakers - Adidas',
                'meta_description' => 'Iconic Adidas sneakers. Comfortable cushioning, durable sole, classic design.',
                'is_active' => true,
                'is_featured' => true,
                'is_hot' => true,
                'is_new' => false
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}