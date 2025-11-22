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
     * 
     * This seeder handles:
     * 1. Products WITH variants (multiple colors) - creates product images and variant-specific images
     * 2. Products WITHOUT variants (single color or single item) - creates only product images
     * 3. Properly sets is_primary flag for products with multiple images
     */
    public function run(): void
    {
        // Get products WITH variants (multiple colors)
        $fliptopTee = Product::where('slug', 'fliptop-collaboration-t-shirt')->first();
        $ubecTee = Product::where('slug', 'ubec-classic-t-shirt')->first();
        
        // Get products WITHOUT variants (single color or single item)
        $rxpandaTee = Product::where('slug', 'rxpanda-collaboration-t-shirt')->first();
        $turbohecticTee = Product::where('slug', 'turbohectic-collaboration-t-shirt')->first();
        $sweatshirt = Product::where('slug', 'premium-sweatshirt')->first();
        $socks = Product::where('slug', 'athletic-socks')->first();
        $towel = Product::where('slug', 'sports-towel')->first();
        $sportsWatch = Product::where('slug', 'classic-sports-watch')->first();
        $leatherWatch = Product::where('slug', 'premium-leather-watch')->first();
        $fitnessTracker = Product::where('slug', 'smart-fitness-tracker')->first();
        $baseballCap = Product::where('slug', 'classic-baseball-cap')->first();
        $silverNecklace = Product::where('slug', 'elegant-silver-necklace')->first();
        $goldBracelet = Product::where('slug', 'classic-gold-bracelet')->first();

        // Define the mapping of products to their images
        // Structure: 'main' = primary image, 'additional' = additional images
        // For products with multiple images, the first one should be is_primary = true
        $productImages = [
            // ============================================
            // PRODUCTS WITH VARIANTS (Multiple Colors)
            // ============================================
            
            'fliptop-collaboration-t-shirt' => [
                // Main product image (PRIMARY)
                'main' => 'RapolloxFlipTop_Tshirt_Black_1.jpg',
                // Additional product images (NOT primary)
                'additional' => [
                    'RapolloxFlipTop_Tshirt_Black_2.jpg'
                ],
                // Variant-specific images (by color)
                'variants' => [
                    'red' => [
                        'RapolloxFlipTop_Tshirt_Red_1.jpg', // PRIMARY for red variant
                        'RapolloxFlipTop_Tshirt_Red_2.jpg'
                    ],
                    'brown' => [
                        'RapolloxFlipTop_Tshirt_Brown_1.jpg', // PRIMARY for brown variant
                        'RapolloxFlipTop_Tshirt_Brown_2.jpg'
                    ]
                ]
            ],
            
            'ubec-classic-t-shirt' => [
                'main' => 'Rapollo_Ubec_Tshirt_White.jpg', // PRIMARY
                'additional' => [],
                'variants' => [
                    'brown' => [
                        'Rapollo_Ubec_Tshirt_Brown_1.jpg', // PRIMARY for brown variant
                        'Rapollo_Ubec_Tshirt_Brown_2.jpg'
                    ]
                ]
            ],

            // ============================================
            // PRODUCTS WITHOUT VARIANTS (Single Color or Single Item)
            // ============================================
            
            'rxpanda-collaboration-t-shirt' => [
                'main' => 'RapolloxRxPanda_Tshirt_Black_1.jpg', // PRIMARY
                'additional' => [
                    'RapolloxRxPanda_Tshirt_Black_2.jpg'
                ],
                'variants' => []
            ],
            
            'turbohectic-collaboration-t-shirt' => [
                'main' => 'RapolloxTurboHectic_Tshirt_Black_1.jpg', // PRIMARY
                'additional' => [
                    'RapolloxTurboHectic_Tshirt_Black_2.jpg'
                ],
                'variants' => []
            ],
            
            'premium-sweatshirt' => [
                'main' => 'Rapollo_Sweatshirt_White.jpg', // PRIMARY (single image)
                'additional' => [],
                'variants' => []
            ],
            
            'athletic-socks' => [
                'main' => 'Rapollo_Socks_Red_1.jpg', // PRIMARY
                'additional' => [
                    'Rapollo_Socks_Red_2.jpg'
                ],
                'variants' => []
            ],
            
            'sports-towel' => [
                'main' => 'Rapollo_Towel_Black_1.jpg', // PRIMARY
                'additional' => [
                    'Rapollo_Towel_Black_2.jpg'
                ],
                'variants' => []
            ],
            
            // Note: The following products don't have actual image files yet
            // They will be created with placeholder logic or skipped if images don't exist
            'classic-sports-watch' => [
                'main' => 'Rapollo_Sports_Watch_1.jpg', // PRIMARY (placeholder - file doesn't exist)
                'additional' => [],
                'variants' => []
            ],
            
            'premium-leather-watch' => [
                'main' => 'Rapollo_Leather_Watch_1.jpg', // PRIMARY (placeholder - file doesn't exist)
                'additional' => [],
                'variants' => []
            ],
            
            'smart-fitness-tracker' => [
                'main' => 'Rapollo_Fitness_Tracker_1.jpg', // PRIMARY (placeholder - file doesn't exist)
                'additional' => [],
                'variants' => []
            ],
            
            'classic-baseball-cap' => [
                'main' => 'Rapollo_Baseball_Cap_1.jpg', // PRIMARY (placeholder - file doesn't exist)
                'additional' => [],
                'variants' => []
            ],
            
            'elegant-silver-necklace' => [
                'main' => 'Rapollo_Silver_Necklace_1.jpg', // PRIMARY (placeholder - file doesn't exist)
                'additional' => [],
                'variants' => []
            ],
            
            'classic-gold-bracelet' => [
                'main' => 'Rapollo_Gold_Bracelet_1.jpg', // PRIMARY (placeholder - file doesn't exist)
                'additional' => [],
                'variants' => []
            ]
        ];

        foreach ($productImages as $productSlug => $imageData) {
            $product = Product::where('slug', $productSlug)->first();
            if (!$product) {
                $this->command->warn("Product not found: {$productSlug}");
                continue;
            }

            // ============================================
            // STEP 1: Upload and create PRIMARY product image
            // ============================================
            $mainImagePath = $this->uploadProductImages($productSlug, [$imageData['main']]);
            if (empty($mainImagePath)) {
                $this->command->warn("Failed to upload main image for {$product->name} - image file may not exist: {$imageData['main']}");
                continue;
            }

            // Create PRIMARY product image (is_primary = true)
            ProductImage::create([
                'product_id' => $product->id,
                'variant_id' => null,
                'url' => $mainImagePath[0],
                'is_primary' => true, // PRIMARY IMAGE
                'sort_order' => 1,
            ]);
            $this->command->info("Created PRIMARY product image for {$product->name}: {$mainImagePath[0]}");

            // ============================================
            // STEP 2: Upload and create additional product images (NOT primary)
            // ============================================
            $sortOrder = 2;
            if (!empty($imageData['additional'])) {
                $additionalImagePaths = $this->uploadProductImages($productSlug, $imageData['additional']);
                foreach ($additionalImagePaths as $uploadedPath) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'variant_id' => null,
                        'url' => $uploadedPath,
                        'is_primary' => false, // NOT primary
                        'sort_order' => $sortOrder,
                    ]);
                    $this->command->info("Created additional product image for {$product->name}: {$uploadedPath}");
                    $sortOrder++;
                }
            }

            // ============================================
            // STEP 3: Upload and create variant-specific images (only for products with variants)
            // ============================================
            if (!empty($imageData['variants'])) {
                // Preload variants for the product
                $variants = ProductVariant::where('product_id', $product->id)
                    ->with('color')
                    ->get();

                // Simple color detection from filename
                $detectColor = function (string $path): ?string {
                    $map = [
                        'black' => 'Black', 'white' => 'White', 'red' => 'Red', 
                        'brown' => 'Brown', 'blue' => 'Blue', 'gray' => 'Gray', 
                        'grey' => 'Gray', 'navy' => 'Navy', 'beige' => 'Beige'
                    ];
                    $lower = strtolower($path);
                    foreach ($map as $needle => $colorName) {
                        if (str_contains($lower, $needle)) {
                            return $colorName;
                        }
                    }
                    return null;
                };

                foreach ($imageData['variants'] as $colorKey => $variantImages) {
                    // Upload variant images
                    $variantImagePaths = $this->uploadProductImages($productSlug, $variantImages);
                    
                    // Find variant with matching color
                    $colorName = ucfirst($colorKey);
                    $variant = $variants->first(function ($v) use ($colorName) {
                        return optional($v->color)->name === $colorName;
                    });

                    if (!$variant) {
                        $this->command->warn("No variant found for color '{$colorName}' in product {$product->name}");
                        continue;
                    }

                    // Create variant images - first one is PRIMARY for this variant
                    $variantSortOrder = 1;
                    foreach ($variantImagePaths as $index => $uploadedPath) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'variant_id' => $variant->id,
                            'url' => $uploadedPath,
                            'is_primary' => ($index === 0), // First variant image is PRIMARY for this variant
                            'sort_order' => $variantSortOrder,
                        ]);
                        $this->command->info("Created variant image for {$product->name} ({$colorName}, variant #{$variant->id}): {$uploadedPath} [is_primary: " . ($index === 0 ? 'true' : 'false') . "]");
                        $variantSortOrder++;
                    }
                }
            }
        }
    }
}
