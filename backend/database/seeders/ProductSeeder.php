<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Subcategory;
use App\Models\Color;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing brands
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
        $bags = Subcategory::where('slug', 'bags')->first();

        // Get colors for default color assignment
        $black = Color::where('name', 'Black')->first();
        $white = Color::where('name', 'White')->first();
        $red = Color::where('name', 'Red')->first();

        $products = [
            [
                'subcategory_id' => $mensTshirts->id,
                'brand_id' => $nike->id,
                'default_color_id' => $black->id, // Black is the main/default color
                'name' => 'FlipTop Collaboration T-Shirt',
                'slug' => 'fliptop-collaboration-t-shirt',
                'description' => 'Official collaboration t-shirt featuring FlipTop branding. Made from premium cotton blend for comfort and durability. Perfect for rap battle events and streetwear enthusiasts.',
                'meta_title' => 'FlipTop Collaboration T-Shirt - Nike',
                'meta_description' => 'Official FlipTop collaboration t-shirt. Premium cotton blend, authentic Filipino rap culture.',
                'is_active' => true,
                'is_featured' => true,
                'is_hot' => true,
                'is_new' => true
            ],
            [
                'subcategory_id' => $mensTshirts->id,
                'brand_id' => $adidas->id,
                'default_color_id' => $black->id, // Black is the only color
                'name' => 'RxPanda Collaboration T-Shirt',
                'slug' => 'rxpanda-collaboration-t-shirt',
                'description' => 'Exclusive collaboration t-shirt with RxPanda featuring unique graphic designs. Soft cotton material with vibrant prints that represent Filipino rap culture and street art.',
                'meta_title' => 'RxPanda Collaboration T-Shirt - Adidas',
                'meta_description' => 'RxPanda collaboration t-shirt. Unique street art designs, soft cotton material.',
                'is_active' => true,
                'is_featured' => true,
                'is_hot' => false,
                'is_new' => true
            ],
            [
                'subcategory_id' => $mensTshirts->id,
                'brand_id' => $puma->id,
                'default_color_id' => $black->id, // Black is the only color
                'name' => 'TurboHectic Collaboration T-Shirt',
                'slug' => 'turbohectic-collaboration-t-shirt',
                'description' => 'High-energy collaboration t-shirt with TurboHectic. Features bold graphics and dynamic designs that capture the intensity of Filipino rap battles and underground music scene.',
                'meta_title' => 'TurboHectic Collaboration T-Shirt - Puma',
                'meta_description' => 'TurboHectic collaboration t-shirt. Bold graphics, dynamic designs, underground rap culture.',
                'is_active' => true,
                'is_featured' => false,
                'is_hot' => true,
                'is_new' => false
            ],
            [
                'subcategory_id' => $mensTshirts->id,
                'brand_id' => $uniqlo->id,
                'default_color_id' => $white->id, // White is the main/default color
                'name' => 'Ubec Classic T-Shirt',
                'slug' => 'ubec-classic-t-shirt',
                'description' => 'Classic Ubec t-shirt featuring clean designs and comfortable fit. Made from high-quality cotton with subtle branding. Perfect for everyday wear and casual occasions.',
                'meta_title' => 'Ubec Classic T-Shirt - Uniqlo',
                'meta_description' => 'Classic Ubec t-shirt. High-quality cotton, comfortable fit, subtle branding.',
                'is_active' => true,
                'is_featured' => true,
                'is_hot' => false,
                'is_new' => false
            ],
            [
                'subcategory_id' => $mensShirts->id,
                'brand_id' => $zara->id,
                'default_color_id' => $white->id, // White is the only color
                'name' => 'Premium Sweatshirt',
                'slug' => 'premium-sweatshirt',
                'description' => 'Comfortable and stylish sweatshirt perfect for cooler weather. Features soft fleece lining and relaxed fit. Ideal for layering or wearing on its own.',
                'meta_title' => 'Premium Sweatshirt - Zara',
                'meta_description' => 'Comfortable premium sweatshirt. Soft fleece lining, relaxed fit, perfect for layering.',
                'is_active' => true,
                'is_featured' => false,
                'is_hot' => false,
                'is_new' => true
            ],
            [
                'subcategory_id' => $bags->id,
                'brand_id' => $hm->id,
                'default_color_id' => $red->id, // Red is the only color
                'name' => 'Athletic Socks',
                'slug' => 'athletic-socks',
                'description' => 'Premium quality athletic socks with comfortable cushioning and moisture-wicking technology. Features subtle branding and comes in various colors to match your style.',
                'meta_title' => 'Athletic Socks - H&M',
                'meta_description' => 'Premium athletic socks. Comfortable cushioning, moisture-wicking, subtle branding.',
                'is_active' => true,
                'is_featured' => false,
                'is_hot' => true,
                'is_new' => false
            ],
            [
                'subcategory_id' => $bags->id,
                'brand_id' => $nike->id,
                'default_color_id' => $black->id, // Black is the only color
                'name' => 'Sports Towel',
                'slug' => 'sports-towel',
                'description' => 'High-quality sports towel made from absorbent cotton material. Perfect for gym, beach, or everyday use. Features durable construction and premium branding.',
                'meta_title' => 'Sports Towel - Nike',
                'meta_description' => 'High-quality sports towel. Absorbent cotton, durable construction, perfect for gym or beach.',
                'is_active' => true,
                'is_featured' => false,
                'is_hot' => false,
                'is_new' => true
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}