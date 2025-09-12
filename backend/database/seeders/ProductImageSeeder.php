<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductImage;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class ProductImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get products
        $classicWhiteTee = Product::where('slug', 'classic-white-t-shirt')->first();
        $graphicTee = Product::where('slug', 'graphic-print-t-shirt')->first();
        $linenShirt = Product::where('slug', 'linen-button-up-shirt')->first();
        $slimJeans = Product::where('slug', 'slim-fit-jeans')->first();
        $snapbackCap = Product::where('slug', 'snapback-cap')->first();
        $sportsWatch = Product::where('slug', 'sports-watch')->first();

        // Get some variants for variant-specific images
        $whiteTeeVariants = ProductVariant::where('product_id', $classicWhiteTee->id)->where('color_id', function($query) {
            $query->select('id')->from('colors')->where('name', 'White');
        })->get();

        $graphicTeeVariants = ProductVariant::where('product_id', $graphicTee->id)->where('color_id', function($query) {
            $query->select('id')->from('colors')->where('name', 'Black');
        })->get();

        $linenShirtVariants = ProductVariant::where('product_id', $linenShirt->id)->where('color_id', function($query) {
            $query->select('id')->from('colors')->where('name', 'Beige');
        })->get();

        $jeansVariants = ProductVariant::where('product_id', $slimJeans->id)->where('color_id', function($query) {
            $query->select('id')->from('colors')->where('name', 'Blue');
        })->get();

        $capVariants = ProductVariant::where('product_id', $snapbackCap->id)->where('color_id', function($query) {
            $query->select('id')->from('colors')->where('name', 'Black');
        })->get();

        $watchVariants = ProductVariant::where('product_id', $sportsWatch->id)->where('color_id', function($query) {
            $query->select('id')->from('colors')->where('name', 'Black');
        })->get();

        // Define the mapping of products to their test images
        $productImages = [
            'classic-white-t-shirt' => 'classic_white_tee.png',
            'graphic-print-t-shirt' => 'graphic_tee.png',
            'linen-button-up-shirt' => 'linen_shirt.png',
            'slim-fit-jeans' => 'slim_fit_jeans.png',
            'snapback-cap' => 'snapback_cap.png',
            'sports-watch' => 'sports_watch.png',
        ];

        // Create storage directories
        Storage::makeDirectory('public/products');
        Storage::makeDirectory('public/variants');
        Storage::makeDirectory('public/brands');

        foreach ($productImages as $productSlug => $imageName) {
            $product = Product::where('slug', $productSlug)->first();
            if (!$product) continue;

            // Source path (frontend test images)
            $sourcePath = base_path('../frontend/public/test_images/' . $imageName);
            
            // Check if source file exists
            if (!File::exists($sourcePath)) {
                $this->command->warn("Source image not found: {$sourcePath}");
                continue;
            }

            // Destination path in storage (relative path only, no /storage/ prefix)
            $relativePath = 'products/' . $productSlug . '_' . $imageName;
            
            // Copy file to storage using File facade
            $destinationFullPath = storage_path('app/public/' . $relativePath);
            File::copy($sourcePath, $destinationFullPath);
            
            // Store only the relative path (without /storage/ prefix)
            $storageUrl = $relativePath;

            // Get variants for this product
            $variants = ProductVariant::where('product_id', $product->id)->get();

            // Create product images
            $images = [
                // Primary image for the first variant
                [
                    'product_id' => $product->id,
                    'variant_id' => $variants->first()?->id,
                    'url' => $storageUrl,
                    'is_primary' => true,
                    'sort_order' => 1
                ],
                // General product image
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'url' => $storageUrl,
                    'is_primary' => false,
                    'sort_order' => 2
                ]
            ];

            foreach ($images as $image) {
                ProductImage::create($image);
            }

            $this->command->info("Uploaded image for {$product->name}: {$storageUrl}");
        }
    }
}