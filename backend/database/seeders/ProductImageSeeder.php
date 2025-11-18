<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductImage;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\Traits\ImageUploadTrait;

class ProductImageSeeder extends Seeder
{
    use ImageUploadTrait;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get products
        $fliptopTee = Product::where('slug', 'fliptop-collaboration-t-shirt')->first();
        $rxpandaTee = Product::where('slug', 'rxpanda-collaboration-t-shirt')->first();
        $turbohecticTee = Product::where('slug', 'turbohectic-collaboration-t-shirt')->first();
        $ubecTee = Product::where('slug', 'ubec-classic-t-shirt')->first();
        $sweatshirt = Product::where('slug', 'premium-sweatshirt')->first();
        $socks = Product::where('slug', 'athletic-socks')->first();
        $towel = Product::where('slug', 'sports-towel')->first();

        // Define the mapping of products to their actual images with proper organization
        $productImages = [
            'fliptop-collaboration-t-shirt' => [
                // Main product (Black) + Color variants
                'main' => 'RapolloxFlipTop_Tshirt_Black_1.jpg',
                'variants' => [
                    'RapolloxFlipTop_Tshirt_Black_1(1).jpg',
                    'RapolloxFlipTop_Tshirt_Black_2.jpg',
                    'RapolloxFlipTop_Tshirt_Red_1.jpg',
                    'RapolloxFlipTop_Tshirt_Red_2.jpg',
                    'RapolloxFlipTop_Tshirt_Brown_1.jpg',
                    'RapolloxFlipTop_Tshirt_Brown_2.jpg'
                ]
            ],
            'rxpanda-collaboration-t-shirt' => [
                // Main product (Black) + variants
                'main' => 'RapolloxRxPanda_Tshirt_Black_1.jpg',
                'variants' => [
                    'RapolloxRxPanda_Tshirt_Black_2.jpg'
                ]
            ],
            'turbohectic-collaboration-t-shirt' => [
                // Main product (Black) + variants
                'main' => 'RapolloxTurboHectic_Tshirt_Black_1.jpg',
                'variants' => [
                    'RapolloxTurboHectic_Tshirt_Black_2.jpg'
                ]
            ],
            'ubec-classic-t-shirt' => [
                // Main product (White) + Color variants
                'main' => 'Rapollo_Ubec_Tshirt_White.jpg',
                'variants' => [
                    'Rapollo_Ubec_Tshirt_Brown_1.jpg',
                    'Rapollo_Ubec_Tshirt_Brown_2.jpg'
                ]
            ],
            'premium-sweatshirt' => [
                // Single product (no variants)
                'main' => 'Rapollo_Sweatshirt_White.jpg',
                'variants' => []
            ],
            'athletic-socks' => [
                // Single product (no variants)
                'main' => 'Rapollo_Socks_Red_1.jpg',
                'variants' => [
                    'Rapollo_Socks_Red_2.jpg'
                ]
            ],
            'sports-towel' => [
                // Single product (no variants)
                'main' => 'Rapollo_Towel_Black_1.jpg',
                'variants' => [
                    'Rapollo_Towel_Black_2.jpg'
                ]
            ]
        ];

        foreach ($productImages as $productSlug => $imageData) {
            $product = Product::where('slug', $productSlug)->first();
            if (!$product) continue;

            // Upload main product image (general product image only)
            $mainImagePath = $this->uploadProductImages($productSlug, [$imageData['main']]);
            if (empty($mainImagePath)) continue;

            // Create main product image as primary (no variant_id)
            ProductImage::create([
                'product_id' => $product->id,
                'variant_id' => null,
                'url' => $mainImagePath[0],
                'is_primary' => true,
                'sort_order' => 1,
            ]);
            $this->command->info("Created PRIMARY product image for {$product->name}: {$mainImagePath[0]}");

            // Upload and attach variant images by color when available
            $variantImagePaths = [];
            if (!empty($imageData['variants'])) {
                $variantImagePaths = $this->uploadProductImages($productSlug, $imageData['variants']);
            }

            // Preload variants for the product
            $variants = ProductVariant::where('product_id', $product->id)->get();

            // Simple color detection from filename
            $detectColor = function (string $path): ?string {
                $map = ['black' => 'Black', 'white' => 'White', 'red' => 'Red', 'brown' => 'Brown', 'blue' => 'Blue', 'gray' => 'Gray', 'grey' => 'Gray', 'navy' => 'Navy', 'beige' => 'Beige'];
                $lower = strtolower($path);
                foreach ($map as $needle => $colorName) {
                    if (str_contains($lower, $needle)) {
                        return $colorName;
                    }
                }
                return null;
            };

            $sortOrder = 2; // start after primary main image
            foreach ($variantImagePaths as $uploadedPath) {
                $colorName = $detectColor($uploadedPath);
                $variantId = null;

                if ($colorName) {
                    // Find any variant with matching color for this product
                    $variant = $variants->first(function ($v) use ($colorName) {
                        return optional($v->color)->name === $colorName;
                    });
                    $variantId = $variant?->id;
                }

                if ($variantId) {
                    // Attach as variant-specific image
                    ProductImage::create([
                        'product_id' => $product->id,
                        'variant_id' => $variantId,
                        'url' => $uploadedPath,
                        'is_primary' => false,
                        'sort_order' => $sortOrder,
                    ]);
                    $this->command->info("Created VARIANT image for {$product->name} ({$colorName}) : {$uploadedPath}");
                } else {
                    // Fallback: attach as general image (no color detected or no variant found)
                    ProductImage::create([
                        'product_id' => $product->id,
                        'variant_id' => null,
                        'url' => $uploadedPath,
                        'is_primary' => false,
                        'sort_order' => $sortOrder,
                    ]);
                    $this->command->warn("No matching variant found for {$product->name} image; attached as general: {$uploadedPath}");
                }

                $sortOrder++;
            }
        }
    }
}