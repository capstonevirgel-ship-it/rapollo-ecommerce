<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Models\Color;
use App\Models\Size;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * NOTE: Only products with MULTIPLE COLORS should have variants.
     * Products with single color (even with sizes) should NOT have variants.
     */
    public function run(): void
    {
        // Get colors
        $black = Color::where('name', 'Black')->first();
        $white = Color::where('name', 'White')->first();
        $red = Color::where('name', 'Red')->first();
        $brown = Color::where('name', 'Brown')->first();

        // Get sizes
        $s = Size::where('name', 'S')->first();
        $m = Size::where('name', 'M')->first();
        $l = Size::where('name', 'L')->first();
        $xl = Size::where('name', 'XL')->first();

        // Get products that HAVE MULTIPLE COLORS (these need variants)
        // NOTE: We only create variants for NON-DEFAULT colors
        // The default color is handled by the product itself, so we don't create variants for it
        $fliptopTee = Product::where('slug', 'fliptop-collaboration-t-shirt')->first();
        $ubecTee = Product::where('slug', 'ubec-classic-t-shirt')->first();

        // Products WITHOUT variants (single color or single item) are NOT included:
        // - rxpanda-collaboration-t-shirt (Black only)
        // - turbohectic-collaboration-t-shirt (Black only)
        // - premium-sweatshirt (White only)
        // - athletic-socks (Red only)
        // - sports-towel (Black only)
        // - classic-sports-watch (single item)
        // - premium-leather-watch (single item)
        // - smart-fitness-tracker (single item)
        // - classic-baseball-cap (single item)
        // - elegant-silver-necklace (single item)
        // - classic-gold-bracelet (single item)

        // Variants inherit price from product - no price fields needed
        $variants = [
            // ============================================
            // FlipTop Collaboration T-Shirt
            // HAS VARIANTS: Black (main), Red, Brown
            // ============================================
            ['product_id' => $fliptopTee->id, 'color_id' => $black->id, 'size_id' => $s->id, 'stock' => 50, 'sku' => 'NKE-FLIP-BLK-S'],
            ['product_id' => $fliptopTee->id, 'color_id' => $black->id, 'size_id' => $m->id, 'stock' => 75, 'sku' => 'NKE-FLIP-BLK-M'],
            ['product_id' => $fliptopTee->id, 'color_id' => $black->id, 'size_id' => $l->id, 'stock' => 60, 'sku' => 'NKE-FLIP-BLK-L'],
            ['product_id' => $fliptopTee->id, 'color_id' => $black->id, 'size_id' => $xl->id, 'stock' => 40, 'sku' => 'NKE-FLIP-BLK-XL'],
            ['product_id' => $fliptopTee->id, 'color_id' => $red->id, 'size_id' => $s->id, 'stock' => 30, 'sku' => 'NKE-FLIP-RED-S'],
            ['product_id' => $fliptopTee->id, 'color_id' => $red->id, 'size_id' => $m->id, 'stock' => 50, 'sku' => 'NKE-FLIP-RED-M'],
            ['product_id' => $fliptopTee->id, 'color_id' => $red->id, 'size_id' => $l->id, 'stock' => 40, 'sku' => 'NKE-FLIP-RED-L'],
            ['product_id' => $fliptopTee->id, 'color_id' => $brown->id, 'size_id' => $s->id, 'stock' => 25, 'sku' => 'NKE-FLIP-BRN-S'],
            ['product_id' => $fliptopTee->id, 'color_id' => $brown->id, 'size_id' => $m->id, 'stock' => 35, 'sku' => 'NKE-FLIP-BRN-M'],
            ['product_id' => $fliptopTee->id, 'color_id' => $brown->id, 'size_id' => $l->id, 'stock' => 30, 'sku' => 'NKE-FLIP-BRN-L'],

            // ============================================
            // Ubec Classic T-Shirt
            // Default color: White (needs variants for stock tracking per size)
            // HAS VARIANTS: White (default), Brown
            // ============================================
            // White variants (default color - needed for stock tracking)
            ['product_id' => $ubecTee->id, 'color_id' => $white->id, 'size_id' => $s->id, 'stock' => 20, 'sku' => 'UNQ-UBEC-WHT-S'],
            ['product_id' => $ubecTee->id, 'color_id' => $white->id, 'size_id' => $m->id, 'stock' => 35, 'sku' => 'UNQ-UBEC-WHT-M'],
            ['product_id' => $ubecTee->id, 'color_id' => $white->id, 'size_id' => $l->id, 'stock' => 30, 'sku' => 'UNQ-UBEC-WHT-L'],
            // Brown variants
            ['product_id' => $ubecTee->id, 'color_id' => $brown->id, 'size_id' => $s->id, 'stock' => 15, 'sku' => 'UNQ-UBEC-BRN-S'],
            ['product_id' => $ubecTee->id, 'color_id' => $brown->id, 'size_id' => $m->id, 'stock' => 25, 'sku' => 'UNQ-UBEC-BRN-M'],
            ['product_id' => $ubecTee->id, 'color_id' => $brown->id, 'size_id' => $l->id, 'stock' => 20, 'sku' => 'UNQ-UBEC-BRN-L']
        ];

        foreach ($variants as $variant) {
            ProductVariant::create($variant);
        }
    }
}
