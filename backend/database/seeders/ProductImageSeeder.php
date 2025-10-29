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

            // Upload main product image
            $mainImagePath = $this->uploadProductImages($productSlug, [$imageData['main']]);
            if (empty($mainImagePath)) continue;

            // Upload variant images
            $variantImagePaths = [];
            if (!empty($imageData['variants'])) {
                $variantImagePaths = $this->uploadProductImages($productSlug, $imageData['variants']);
            }

            // Combine all images
            $allImagePaths = array_merge($mainImagePath, $variantImagePaths);

            // Get variants for this product
            $variants = ProductVariant::where('product_id', $product->id)->get();

            $sortOrder = 1;
            foreach ($allImagePaths as $uploadedPath) {
                // Create product images
                $images = [
                    // Primary image for the first variant
                    [
                        'product_id' => $product->id,
                        'variant_id' => $variants->first()?->id,
                        'url' => $uploadedPath,
                        'is_primary' => $sortOrder === 1,
                        'sort_order' => $sortOrder
                    ],
                    // General product image
                    [
                        'product_id' => $product->id,
                        'variant_id' => null,
                        'url' => $uploadedPath,
                        'is_primary' => false,
                        'sort_order' => $sortOrder
                    ]
                ];

                foreach ($images as $image) {
                    ProductImage::create($image);
                }

                $this->command->info("Created image records for {$product->name}: {$uploadedPath}");
                $sortOrder++;
            }
        }
    }
}