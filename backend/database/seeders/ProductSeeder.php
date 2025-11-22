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

        // Get subcategories
        $mensTshirts = Subcategory::where('slug', 'mens-t-shirts')->first();
        $mensShirts = Subcategory::where('slug', 'mens-shirts')->first();
        $mensPants = Subcategory::where('slug', 'mens-pants')->first();
        $womensTops = Subcategory::where('slug', 'womens-tops')->first();
        $sneakers = Subcategory::where('slug', 'sneakers')->first();
        $hats = Subcategory::where('slug', 'hats')->first();
        $watches = Subcategory::where('slug', 'watches')->first();
        $bags = Subcategory::where('slug', 'bags')->first();
        $jewelry = Subcategory::where('slug', 'jewelry')->first();
        $wearables = Subcategory::where('slug', 'wearables')->first();

        // Get colors for default color assignment
        $black = Color::where('name', 'Black')->first();
        $white = Color::where('name', 'White')->first();
        $red = Color::where('name', 'Red')->first();
        $blue = Color::where('name', 'Blue')->first();
        $silver = Color::where('name', 'Silver')->first();
        $gold = Color::where('name', 'Gold')->first();

        $products = [
            // ============================================
            // PRODUCTS WITH VARIANTS (Multiple Colors)
            // ============================================
            
            // FlipTop Collaboration T-Shirt - HAS VARIANTS (Black, Red, Brown)
            [
                'subcategory_id' => $mensTshirts->id,
                'brand_id' => $nike->id,
                'default_color_id' => $black->id,
                'name' => 'FlipTop Collaboration T-Shirt',
                'slug' => 'fliptop-collaboration-t-shirt',
                'description' => 'Official collaboration t-shirt featuring FlipTop branding. Made from premium cotton blend for comfort and durability. Perfect for rap battle events and streetwear enthusiasts.',
                'meta_title' => 'FlipTop Collaboration T-Shirt - Nike',
                'meta_description' => 'Official FlipTop collaboration t-shirt. Premium cotton blend, authentic Filipino rap culture.',
                'base_price' => 802.68,
                'price' => 899.00,
                'is_active' => true,
                'is_featured' => true,
                'is_hot' => true,
                'is_new' => true
            ],
            
            // Ubec Classic T-Shirt - HAS VARIANTS (White, Brown)
            [
                'subcategory_id' => $mensTshirts->id,
                'brand_id' => $uniqlo->id,
                'default_color_id' => $white->id,
                'name' => 'Ubec Classic T-Shirt',
                'slug' => 'ubec-classic-t-shirt',
                'description' => 'Classic Ubec t-shirt featuring clean designs and comfortable fit. Made from high-quality cotton with subtle branding. Perfect for everyday wear and casual occasions.',
                'meta_title' => 'Ubec Classic T-Shirt - Uniqlo',
                'meta_description' => 'Classic Ubec t-shirt. High-quality cotton, comfortable fit, subtle branding.',
                'base_price' => 624.11,
                'price' => 699.00,
                'is_active' => true,
                'is_featured' => true,
                'is_hot' => false,
                'is_new' => false
            ],

            // ============================================
            // PRODUCTS WITHOUT VARIANTS (Single Color or Single Item)
            // ============================================
            
            // RxPanda Collaboration T-Shirt - NO VARIANTS (Black only, single item)
            [
                'subcategory_id' => $mensTshirts->id,
                'brand_id' => $adidas->id,
                'default_color_id' => $black->id,
                'name' => 'RxPanda Collaboration T-Shirt',
                'slug' => 'rxpanda-collaboration-t-shirt',
                'description' => 'Exclusive collaboration t-shirt with RxPanda featuring unique graphic designs. Soft cotton material with vibrant prints that represent Filipino rap culture and street art.',
                'meta_title' => 'RxPanda Collaboration T-Shirt - Adidas',
                'meta_description' => 'RxPanda collaboration t-shirt. Unique street art designs, soft cotton material.',
                'base_price' => 713.39,
                'price' => 799.00,
                'stock' => 145, // Total stock for all sizes combined
                'sku' => 'ADD-RXP-BLK',
                'is_active' => true,
                'is_featured' => true,
                'is_hot' => false,
                'is_new' => true
            ],
            
            // TurboHectic Collaboration T-Shirt - NO VARIANTS (Black only, single item)
            [
                'subcategory_id' => $mensTshirts->id,
                'brand_id' => $puma->id,
                'default_color_id' => $black->id,
                'name' => 'TurboHectic Collaboration T-Shirt',
                'slug' => 'turbohectic-collaboration-t-shirt',
                'description' => 'High-energy collaboration t-shirt with TurboHectic. Features bold graphics and dynamic designs that capture the intensity of Filipino rap battles and underground music scene.',
                'meta_title' => 'TurboHectic Collaboration T-Shirt - Puma',
                'meta_description' => 'TurboHectic collaboration t-shirt. Bold graphics, dynamic designs, underground rap culture.',
                'base_price' => 713.39,
                'price' => 799.00,
                'stock' => 145, // Total stock
                'sku' => 'PUM-TURBO-BLK',
                'is_active' => true,
                'is_featured' => false,
                'is_hot' => true,
                'is_new' => false
            ],
            
            // Premium Sweatshirt - NO VARIANTS (White only, single item)
            [
                'subcategory_id' => $mensShirts->id,
                'brand_id' => $zara->id,
                'default_color_id' => $white->id,
                'name' => 'Premium Sweatshirt',
                'slug' => 'premium-sweatshirt',
                'description' => 'Comfortable and stylish sweatshirt perfect for cooler weather. Features soft fleece lining and relaxed fit. Ideal for layering or wearing on its own.',
                'meta_title' => 'Premium Sweatshirt - Zara',
                'meta_description' => 'Comfortable premium sweatshirt. Soft fleece lining, relaxed fit, perfect for layering.',
                'base_price' => 1159.82,
                'price' => 1299.00,
                'stock' => 75, // Total stock
                'sku' => 'ZAR-SWEAT-WHT',
                'is_active' => true,
                'is_featured' => false,
                'is_hot' => false,
                'is_new' => true
            ],
            
            // Athletic Socks - NO VARIANTS (Red only, single item)
            [
                'subcategory_id' => $bags->id,
                'brand_id' => $hm->id,
                'default_color_id' => $red->id,
                'name' => 'Athletic Socks',
                'slug' => 'athletic-socks',
                'description' => 'Premium quality athletic socks with comfortable cushioning and moisture-wicking technology. Features subtle branding and comes in various colors to match your style.',
                'meta_title' => 'Athletic Socks - H&M',
                'meta_description' => 'Premium athletic socks. Comfortable cushioning, moisture-wicking, subtle branding.',
                'base_price' => 266.96,
                'price' => 299.00,
                'stock' => 100,
                'sku' => 'HM-SOCK-RED',
                'is_active' => true,
                'is_featured' => false,
                'is_hot' => true,
                'is_new' => false
            ],
            
            // Sports Towel - NO VARIANTS (Black only, single item)
            [
                'subcategory_id' => $bags->id,
                'brand_id' => $nike->id,
                'default_color_id' => $black->id,
                'name' => 'Sports Towel',
                'slug' => 'sports-towel',
                'description' => 'High-quality sports towel made from absorbent cotton material. Perfect for gym, beach, or everyday use. Features durable construction and premium branding.',
                'meta_title' => 'Sports Towel - Nike',
                'meta_description' => 'High-quality sports towel. Absorbent cotton, durable construction, perfect for gym or beach.',
                'base_price' => 534.82,
                'price' => 599.00,
                'stock' => 25,
                'sku' => 'NKE-TOWEL-BLK',
                'is_active' => true,
                'is_featured' => false,
                'is_hot' => false,
                'is_new' => true
            ],
            
            // Classic Sports Watch - NO VARIANTS (single product)
            [
                'subcategory_id' => $watches->id,
                'brand_id' => $nike->id,
                'default_color_id' => $black->id,
                'name' => 'Classic Sports Watch',
                'slug' => 'classic-sports-watch',
                'description' => 'Elegant sports watch with digital display and water-resistant design. Features stopwatch, timer, and backlight. Perfect for athletes and active individuals.',
                'meta_title' => 'Classic Sports Watch - Nike',
                'meta_description' => 'Elegant sports watch with digital display. Water-resistant, stopwatch, timer features.',
                'base_price' => 2678.57,
                'price' => 2999.00,
                'stock' => 50,
                'sku' => 'NKE-WATCH-001',
                'is_active' => true,
                'is_featured' => true,
                'is_hot' => false,
                'is_new' => true
            ],
            
            // Premium Leather Watch - NO VARIANTS (single product)
            [
                'subcategory_id' => $watches->id,
                'brand_id' => $adidas->id,
                'default_color_id' => $silver->id,
                'name' => 'Premium Leather Watch',
                'slug' => 'premium-leather-watch',
                'description' => 'Sophisticated leather watch with analog display and genuine leather strap. Classic design suitable for both casual and formal occasions.',
                'meta_title' => 'Premium Leather Watch - Adidas',
                'meta_description' => 'Sophisticated leather watch. Genuine leather strap, analog display, classic design.',
                'base_price' => 3566.96,
                'price' => 3999.00,
                'stock' => 30,
                'sku' => 'ADD-WATCH-002',
                'is_active' => true,
                'is_featured' => false,
                'is_hot' => true,
                'is_new' => false
            ],
            
            // Smart Fitness Tracker - NO VARIANTS (single product)
            [
                'subcategory_id' => $wearables->id,
                'brand_id' => $nike->id,
                'default_color_id' => $black->id,
                'name' => 'Smart Fitness Tracker',
                'slug' => 'smart-fitness-tracker',
                'description' => 'Advanced fitness tracker with heart rate monitor, step counter, and sleep tracking. Syncs with mobile app for comprehensive health monitoring.',
                'meta_title' => 'Smart Fitness Tracker - Nike',
                'meta_description' => 'Advanced fitness tracker. Heart rate monitor, step counter, sleep tracking, mobile app sync.',
                'base_price' => 4458.04,
                'price' => 4999.00,
                'stock' => 75,
                'sku' => 'NKE-TRACK-001',
                'is_active' => true,
                'is_featured' => true,
                'is_hot' => true,
                'is_new' => true
            ],
            
            // Classic Baseball Cap - NO VARIANTS (single product)
            [
                'subcategory_id' => $hats->id,
                'brand_id' => $nike->id,
                'default_color_id' => $black->id,
                'name' => 'Classic Baseball Cap',
                'slug' => 'classic-baseball-cap',
                'description' => 'Classic baseball cap with adjustable strap and embroidered logo. Made from breathable cotton material. Perfect for sports and casual wear.',
                'meta_title' => 'Classic Baseball Cap - Nike',
                'meta_description' => 'Classic baseball cap. Adjustable strap, embroidered logo, breathable cotton.',
                'base_price' => 624.11,
                'price' => 699.00,
                'stock' => 100,
                'sku' => 'NKE-CAP-001',
                'is_active' => true,
                'is_featured' => false,
                'is_hot' => false,
                'is_new' => true
            ],
            
            // Elegant Silver Necklace - NO VARIANTS (single product)
            [
                'subcategory_id' => $jewelry->id,
                'brand_id' => $hm->id,
                'default_color_id' => $silver->id,
                'name' => 'Elegant Silver Necklace',
                'slug' => 'elegant-silver-necklace',
                'description' => 'Beautiful silver necklace with delicate chain design. Perfect for special occasions or everyday elegance. Hypoallergenic and tarnish-resistant.',
                'meta_title' => 'Elegant Silver Necklace - H&M',
                'meta_description' => 'Beautiful silver necklace. Delicate chain design, hypoallergenic, tarnish-resistant.',
                'base_price' => 1339.29,
                'price' => 1499.00,
                'stock' => 40,
                'sku' => 'HM-NECK-001',
                'is_active' => true,
                'is_featured' => false,
                'is_hot' => true,
                'is_new' => false
            ],
            
            // Classic Gold Bracelet - NO VARIANTS (single product)
            [
                'subcategory_id' => $jewelry->id,
                'brand_id' => $zara->id,
                'default_color_id' => $gold->id,
                'name' => 'Classic Gold Bracelet',
                'slug' => 'classic-gold-bracelet',
                'description' => 'Timeless gold bracelet with intricate design. Made from high-quality materials with elegant finish. Perfect gift for special occasions.',
                'meta_title' => 'Classic Gold Bracelet - Zara',
                'meta_description' => 'Timeless gold bracelet. Intricate design, high-quality materials, elegant finish.',
                'base_price' => 1785.71,
                'price' => 1999.00,
                'stock' => 25,
                'sku' => 'ZAR-BRAC-001',
                'is_active' => true,
                'is_featured' => true,
                'is_hot' => false,
                'is_new' => true
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
