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
        $brown = Color::where('name', 'Brown')->first();

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

        // Get Rapollo products
        $fliptopTee = Product::where('slug', 'fliptop-collaboration-t-shirt')->first();
        $rxpandaTee = Product::where('slug', 'rxpanda-collaboration-t-shirt')->first();
        $turbohecticTee = Product::where('slug', 'turbohectic-collaboration-t-shirt')->first();
        $ubecTee = Product::where('slug', 'ubec-classic-t-shirt')->first();
        $sweatshirt = Product::where('slug', 'premium-sweatshirt')->first();
        $socks = Product::where('slug', 'athletic-socks')->first();
        $towel = Product::where('slug', 'sports-towel')->first();

        $variants = [
            // FlipTop Collaboration T-Shirt variants (Black main + Red/Brown variants)
            ['product_id' => $fliptopTee->id, 'color_id' => $black->id, 'size_id' => $s->id, 'price' => 899.00, 'stock' => 50, 'sku' => 'NKE-FLIP-BLK-S'],
            ['product_id' => $fliptopTee->id, 'color_id' => $black->id, 'size_id' => $m->id, 'price' => 899.00, 'stock' => 75, 'sku' => 'NKE-FLIP-BLK-M'],
            ['product_id' => $fliptopTee->id, 'color_id' => $black->id, 'size_id' => $l->id, 'price' => 899.00, 'stock' => 60, 'sku' => 'NKE-FLIP-BLK-L'],
            ['product_id' => $fliptopTee->id, 'color_id' => $black->id, 'size_id' => $xl->id, 'price' => 899.00, 'stock' => 40, 'sku' => 'NKE-FLIP-BLK-XL'],
            ['product_id' => $fliptopTee->id, 'color_id' => $red->id, 'size_id' => $s->id, 'price' => 899.00, 'stock' => 30, 'sku' => 'NKE-FLIP-RED-S'],
            ['product_id' => $fliptopTee->id, 'color_id' => $red->id, 'size_id' => $m->id, 'price' => 899.00, 'stock' => 50, 'sku' => 'NKE-FLIP-RED-M'],
            ['product_id' => $fliptopTee->id, 'color_id' => $red->id, 'size_id' => $l->id, 'price' => 899.00, 'stock' => 40, 'sku' => 'NKE-FLIP-RED-L'],
            ['product_id' => $fliptopTee->id, 'color_id' => $brown->id, 'size_id' => $s->id, 'price' => 899.00, 'stock' => 25, 'sku' => 'NKE-FLIP-BRN-S'],
            ['product_id' => $fliptopTee->id, 'color_id' => $brown->id, 'size_id' => $m->id, 'price' => 899.00, 'stock' => 35, 'sku' => 'NKE-FLIP-BRN-M'],
            ['product_id' => $fliptopTee->id, 'color_id' => $brown->id, 'size_id' => $l->id, 'price' => 899.00, 'stock' => 30, 'sku' => 'NKE-FLIP-BRN-L'],

            // RxPanda Collaboration T-Shirt variants (Black only)
            ['product_id' => $rxpandaTee->id, 'color_id' => $black->id, 'size_id' => $s->id, 'price' => 799.00, 'stock' => 30, 'sku' => 'ADD-RXP-BLK-S'],
            ['product_id' => $rxpandaTee->id, 'color_id' => $black->id, 'size_id' => $m->id, 'price' => 799.00, 'stock' => 50, 'sku' => 'ADD-RXP-BLK-M'],
            ['product_id' => $rxpandaTee->id, 'color_id' => $black->id, 'size_id' => $l->id, 'price' => 799.00, 'stock' => 40, 'sku' => 'ADD-RXP-BLK-L'],
            ['product_id' => $rxpandaTee->id, 'color_id' => $black->id, 'size_id' => $xl->id, 'price' => 799.00, 'stock' => 25, 'sku' => 'ADD-RXP-BLK-XL'],

            // TurboHectic Collaboration T-Shirt variants (Black only)
            ['product_id' => $turbohecticTee->id, 'color_id' => $black->id, 'size_id' => $s->id, 'price' => 799.00, 'stock' => 30, 'sku' => 'PUM-TURBO-BLK-S'],
            ['product_id' => $turbohecticTee->id, 'color_id' => $black->id, 'size_id' => $m->id, 'price' => 799.00, 'stock' => 50, 'sku' => 'PUM-TURBO-BLK-M'],
            ['product_id' => $turbohecticTee->id, 'color_id' => $black->id, 'size_id' => $l->id, 'price' => 799.00, 'stock' => 40, 'sku' => 'PUM-TURBO-BLK-L'],
            ['product_id' => $turbohecticTee->id, 'color_id' => $black->id, 'size_id' => $xl->id, 'price' => 799.00, 'stock' => 25, 'sku' => 'PUM-TURBO-BLK-XL'],

            // Ubec Classic T-Shirt variants (White main + Brown variants)
            ['product_id' => $ubecTee->id, 'color_id' => $white->id, 'size_id' => $s->id, 'price' => 699.00, 'stock' => 20, 'sku' => 'UNQ-UBEC-WHT-S'],
            ['product_id' => $ubecTee->id, 'color_id' => $white->id, 'size_id' => $m->id, 'price' => 699.00, 'stock' => 35, 'sku' => 'UNQ-UBEC-WHT-M'],
            ['product_id' => $ubecTee->id, 'color_id' => $white->id, 'size_id' => $l->id, 'price' => 699.00, 'stock' => 30, 'sku' => 'UNQ-UBEC-WHT-L'],
            ['product_id' => $ubecTee->id, 'color_id' => $brown->id, 'size_id' => $s->id, 'price' => 699.00, 'stock' => 15, 'sku' => 'UNQ-UBEC-BRN-S'],
            ['product_id' => $ubecTee->id, 'color_id' => $brown->id, 'size_id' => $m->id, 'price' => 699.00, 'stock' => 25, 'sku' => 'UNQ-UBEC-BRN-M'],
            ['product_id' => $ubecTee->id, 'color_id' => $brown->id, 'size_id' => $l->id, 'price' => 699.00, 'stock' => 20, 'sku' => 'UNQ-UBEC-BRN-L'],

            // Premium Sweatshirt variants (White only)
            ['product_id' => $sweatshirt->id, 'color_id' => $white->id, 'size_id' => $s->id, 'price' => 1299.00, 'stock' => 15, 'sku' => 'ZAR-SWEAT-WHT-S'],
            ['product_id' => $sweatshirt->id, 'color_id' => $white->id, 'size_id' => $m->id, 'price' => 1299.00, 'stock' => 25, 'sku' => 'ZAR-SWEAT-WHT-M'],
            ['product_id' => $sweatshirt->id, 'color_id' => $white->id, 'size_id' => $l->id, 'price' => 1299.00, 'stock' => 20, 'sku' => 'ZAR-SWEAT-WHT-L'],
            ['product_id' => $sweatshirt->id, 'color_id' => $white->id, 'size_id' => $xl->id, 'price' => 1299.00, 'stock' => 15, 'sku' => 'ZAR-SWEAT-WHT-XL'],

            // Athletic Socks variants (Red only)
            ['product_id' => $socks->id, 'color_id' => $red->id, 'size_id' => $oneSize->id, 'price' => 299.00, 'stock' => 100, 'sku' => 'HM-SOCK-RED-OS'],

            // Sports Towel variants (Black only)
            ['product_id' => $towel->id, 'color_id' => $black->id, 'size_id' => $oneSize->id, 'price' => 599.00, 'stock' => 25, 'sku' => 'NKE-TOWEL-BLK-OS']
        ];

        foreach ($variants as $variant) {
            ProductVariant::create($variant);
        }
    }
}