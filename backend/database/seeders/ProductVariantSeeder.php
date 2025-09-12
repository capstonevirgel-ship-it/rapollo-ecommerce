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
     */
    public function run(): void
    {
        // Get colors
        $black = Color::where('name', 'Black')->first();
        $white = Color::where('name', 'White')->first();
        $blue = Color::where('name', 'Blue')->first();
        $red = Color::where('name', 'Red')->first();
        $gray = Color::where('name', 'Gray')->first();
        $navy = Color::where('name', 'Navy')->first();
        $beige = Color::where('name', 'Beige')->first();

        // Get sizes
        $xs = Size::where('name', 'XS')->first();
        $s = Size::where('name', 'S')->first();
        $m = Size::where('name', 'M')->first();
        $l = Size::where('name', 'L')->first();
        $xl = Size::where('name', 'XL')->first();
        $size6 = Size::where('name', '6')->first();
        $size7 = Size::where('name', '7')->first();
        $size8 = Size::where('name', '8')->first();
        $size9 = Size::where('name', '9')->first();
        $size10 = Size::where('name', '10')->first();
        $size11 = Size::where('name', '11')->first();
        $size12 = Size::where('name', '12')->first();
        $oneSize = Size::where('name', 'One Size')->first();

        // Get products
        $classicWhiteTee = Product::where('slug', 'classic-white-t-shirt')->first();
        $graphicTee = Product::where('slug', 'graphic-print-t-shirt')->first();
        $linenShirt = Product::where('slug', 'linen-button-up-shirt')->first();
        $slimJeans = Product::where('slug', 'slim-fit-jeans')->first();
        $snapbackCap = Product::where('slug', 'snapback-cap')->first();
        $sportsWatch = Product::where('slug', 'sports-watch')->first();
        $casualBlouse = Product::where('slug', 'casual-blouse')->first();
        $classicSneakers = Product::where('slug', 'classic-sneakers')->first();

        $variants = [
            // Classic White T-Shirt variants
            ['product_id' => $classicWhiteTee->id, 'color_id' => $white->id, 'size_id' => $s->id, 'price' => 24.99, 'stock' => 50, 'sku' => 'NKE-WHT-TEE-S'],
            ['product_id' => $classicWhiteTee->id, 'color_id' => $white->id, 'size_id' => $m->id, 'price' => 24.99, 'stock' => 75, 'sku' => 'NKE-WHT-TEE-M'],
            ['product_id' => $classicWhiteTee->id, 'color_id' => $white->id, 'size_id' => $l->id, 'price' => 24.99, 'stock' => 60, 'sku' => 'NKE-WHT-TEE-L'],
            ['product_id' => $classicWhiteTee->id, 'color_id' => $white->id, 'size_id' => $xl->id, 'price' => 24.99, 'stock' => 40, 'sku' => 'NKE-WHT-TEE-XL'],
            ['product_id' => $classicWhiteTee->id, 'color_id' => $black->id, 'size_id' => $s->id, 'price' => 24.99, 'stock' => 45, 'sku' => 'NKE-BLK-TEE-S'],
            ['product_id' => $classicWhiteTee->id, 'color_id' => $black->id, 'size_id' => $m->id, 'price' => 24.99, 'stock' => 70, 'sku' => 'NKE-BLK-TEE-M'],
            ['product_id' => $classicWhiteTee->id, 'color_id' => $black->id, 'size_id' => $l->id, 'price' => 24.99, 'stock' => 55, 'sku' => 'NKE-BLK-TEE-L'],

            // Graphic Print T-Shirt variants
            ['product_id' => $graphicTee->id, 'color_id' => $black->id, 'size_id' => $s->id, 'price' => 29.99, 'stock' => 30, 'sku' => 'ADD-BLK-GRAPHIC-S'],
            ['product_id' => $graphicTee->id, 'color_id' => $black->id, 'size_id' => $m->id, 'price' => 29.99, 'stock' => 50, 'sku' => 'ADD-BLK-GRAPHIC-M'],
            ['product_id' => $graphicTee->id, 'color_id' => $black->id, 'size_id' => $l->id, 'price' => 29.99, 'stock' => 40, 'sku' => 'ADD-BLK-GRAPHIC-L'],
            ['product_id' => $graphicTee->id, 'color_id' => $white->id, 'size_id' => $s->id, 'price' => 29.99, 'stock' => 25, 'sku' => 'ADD-WHT-GRAPHIC-S'],
            ['product_id' => $graphicTee->id, 'color_id' => $white->id, 'size_id' => $m->id, 'price' => 29.99, 'stock' => 45, 'sku' => 'ADD-WHT-GRAPHIC-M'],

            // Linen Button-Up Shirt variants
            ['product_id' => $linenShirt->id, 'color_id' => $beige->id, 'size_id' => $s->id, 'price' => 39.99, 'stock' => 20, 'sku' => 'UNQ-BEIGE-LINEN-S'],
            ['product_id' => $linenShirt->id, 'color_id' => $beige->id, 'size_id' => $m->id, 'price' => 39.99, 'stock' => 35, 'sku' => 'UNQ-BEIGE-LINEN-M'],
            ['product_id' => $linenShirt->id, 'color_id' => $beige->id, 'size_id' => $l->id, 'price' => 39.99, 'stock' => 30, 'sku' => 'UNQ-BEIGE-LINEN-L'],
            ['product_id' => $linenShirt->id, 'color_id' => $white->id, 'size_id' => $s->id, 'price' => 39.99, 'stock' => 15, 'sku' => 'UNQ-WHT-LINEN-S'],
            ['product_id' => $linenShirt->id, 'color_id' => $white->id, 'size_id' => $m->id, 'price' => 39.99, 'stock' => 25, 'sku' => 'UNQ-WHT-LINEN-M'],

            // Slim Fit Jeans variants
            ['product_id' => $slimJeans->id, 'color_id' => $blue->id, 'size_id' => $s->id, 'price' => 59.99, 'stock' => 25, 'sku' => 'ZAR-BLU-JEANS-S'],
            ['product_id' => $slimJeans->id, 'color_id' => $blue->id, 'size_id' => $m->id, 'price' => 59.99, 'stock' => 40, 'sku' => 'ZAR-BLU-JEANS-M'],
            ['product_id' => $slimJeans->id, 'color_id' => $blue->id, 'size_id' => $l->id, 'price' => 59.99, 'stock' => 35, 'sku' => 'ZAR-BLU-JEANS-L'],
            ['product_id' => $slimJeans->id, 'color_id' => $black->id, 'size_id' => $s->id, 'price' => 59.99, 'stock' => 20, 'sku' => 'ZAR-BLK-JEANS-S'],
            ['product_id' => $slimJeans->id, 'color_id' => $black->id, 'size_id' => $m->id, 'price' => 59.99, 'stock' => 30, 'sku' => 'ZAR-BLK-JEANS-M'],

            // Snapback Cap variants
            ['product_id' => $snapbackCap->id, 'color_id' => $black->id, 'size_id' => $oneSize->id, 'price' => 19.99, 'stock' => 100, 'sku' => 'PUM-BLK-CAP-OS'],
            ['product_id' => $snapbackCap->id, 'color_id' => $white->id, 'size_id' => $oneSize->id, 'price' => 19.99, 'stock' => 80, 'sku' => 'PUM-WHT-CAP-OS'],
            ['product_id' => $snapbackCap->id, 'color_id' => $red->id, 'size_id' => $oneSize->id, 'price' => 19.99, 'stock' => 60, 'sku' => 'PUM-RED-CAP-OS'],

            // Sports Watch variants
            ['product_id' => $sportsWatch->id, 'color_id' => $black->id, 'size_id' => $oneSize->id, 'price' => 89.99, 'stock' => 25, 'sku' => 'NKE-BLK-WATCH-OS'],
            ['product_id' => $sportsWatch->id, 'color_id' => $white->id, 'size_id' => $oneSize->id, 'price' => 89.99, 'stock' => 20, 'sku' => 'NKE-WHT-WATCH-OS'],
            ['product_id' => $sportsWatch->id, 'color_id' => $blue->id, 'size_id' => $oneSize->id, 'price' => 89.99, 'stock' => 15, 'sku' => 'NKE-BLU-WATCH-OS'],

            // Casual Blouse variants
            ['product_id' => $casualBlouse->id, 'color_id' => $white->id, 'size_id' => $xs->id, 'price' => 34.99, 'stock' => 15, 'sku' => 'HM-WHT-BLOUSE-XS'],
            ['product_id' => $casualBlouse->id, 'color_id' => $white->id, 'size_id' => $s->id, 'price' => 34.99, 'stock' => 25, 'sku' => 'HM-WHT-BLOUSE-S'],
            ['product_id' => $casualBlouse->id, 'color_id' => $white->id, 'size_id' => $m->id, 'price' => 34.99, 'stock' => 30, 'sku' => 'HM-WHT-BLOUSE-M'],
            ['product_id' => $casualBlouse->id, 'color_id' => $blue->id, 'size_id' => $s->id, 'price' => 34.99, 'stock' => 20, 'sku' => 'HM-BLU-BLOUSE-S'],
            ['product_id' => $casualBlouse->id, 'color_id' => $blue->id, 'size_id' => $m->id, 'price' => 34.99, 'stock' => 25, 'sku' => 'HM-BLU-BLOUSE-M'],

            // Classic Sneakers variants
            ['product_id' => $classicSneakers->id, 'color_id' => $white->id, 'size_id' => $size6->id, 'price' => 79.99, 'stock' => 20, 'sku' => 'ADD-WHT-SNEAK-6'],
            ['product_id' => $classicSneakers->id, 'color_id' => $white->id, 'size_id' => $size7->id, 'price' => 79.99, 'stock' => 25, 'sku' => 'ADD-WHT-SNEAK-7'],
            ['product_id' => $classicSneakers->id, 'color_id' => $white->id, 'size_id' => $size8->id, 'price' => 79.99, 'stock' => 30, 'sku' => 'ADD-WHT-SNEAK-8'],
            ['product_id' => $classicSneakers->id, 'color_id' => $white->id, 'size_id' => $size9->id, 'price' => 79.99, 'stock' => 35, 'sku' => 'ADD-WHT-SNEAK-9'],
            ['product_id' => $classicSneakers->id, 'color_id' => $white->id, 'size_id' => $size10->id, 'price' => 79.99, 'stock' => 40, 'sku' => 'ADD-WHT-SNEAK-10'],
            ['product_id' => $classicSneakers->id, 'color_id' => $white->id, 'size_id' => $size11->id, 'price' => 79.99, 'stock' => 25, 'sku' => 'ADD-WHT-SNEAK-11'],
            ['product_id' => $classicSneakers->id, 'color_id' => $black->id, 'size_id' => $size8->id, 'price' => 79.99, 'stock' => 20, 'sku' => 'ADD-BLK-SNEAK-8'],
            ['product_id' => $classicSneakers->id, 'color_id' => $black->id, 'size_id' => $size9->id, 'price' => 79.99, 'stock' => 25, 'sku' => 'ADD-BLK-SNEAK-9'],
            ['product_id' => $classicSneakers->id, 'color_id' => $black->id, 'size_id' => $size10->id, 'price' => 79.99, 'stock' => 30, 'sku' => 'ADD-BLK-SNEAK-10']
        ];

        foreach ($variants as $variant) {
            ProductVariant::create($variant);
        }
    }
}