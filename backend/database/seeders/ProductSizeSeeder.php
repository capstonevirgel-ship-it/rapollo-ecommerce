<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Size;

class ProductSizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Attaches sizes to products that don't have variants but need size selection.
     */
    public function run(): void
    {
        // Get sizes
        $s = Size::where('name', 'S')->first();
        $m = Size::where('name', 'M')->first();
        $l = Size::where('name', 'L')->first();
        $xl = Size::where('name', 'XL')->first();
        $xs = Size::where('name', 'XS')->first();

        // Get products WITHOUT variants that need sizes
        // These are products that come in different sizes but only one color
        $rxpandaTee = Product::where('slug', 'rxpanda-collaboration-t-shirt')->first();
        $turbohecticTee = Product::where('slug', 'turbohectic-collaboration-t-shirt')->first();
        $sweatshirt = Product::where('slug', 'premium-sweatshirt')->first();

        // Attach sizes to products
        if ($rxpandaTee) {
            $rxpandaTee->sizes()->attach([$s->id, $m->id, $l->id, $xl->id]);
            $this->command->info("Attached sizes to RxPanda T-Shirt");
        }

        if ($turbohecticTee) {
            $turbohecticTee->sizes()->attach([$s->id, $m->id, $l->id, $xl->id]);
            $this->command->info("Attached sizes to TurboHectic T-Shirt");
        }

        if ($sweatshirt) {
            $sweatshirt->sizes()->attach([$s->id, $m->id, $l->id, $xl->id]);
            $this->command->info("Attached sizes to Premium Sweatshirt");
        }
    }
}

