<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Size;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with([
            'brand',
            'defaultColor',
            'subcategory.category', 
            'variants.color',
            'variants.size',
            'variants.images' => function($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('sort_order', 'asc');
            },
            'images' => function($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('sort_order', 'asc');
            }
        ]);

        // Filter by is_active - default to true (active products only) for public requests
        // Allow override via query parameter
        // If 'all' parameter is true, don't filter by is_active (for admin)
        if ($request->has('all') && $request->boolean('all')) {
            // Admin request - show all products regardless of active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            // If 'all' is true and no is_active specified, show all (no filter)
        } else {
            // Public request - default to active products only
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            } else {
                // Default: only show active products for public access
                $query->where('is_active', true);
            }
        }
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }
        if ($request->has('is_hot')) {
            $query->where('is_hot', $request->boolean('is_hot'));
        }
        if ($request->has('is_new')) {
            $query->where('is_new', $request->boolean('is_new'));
        }
        
        // Search filter
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('brand', function ($brandQuery) use ($search) {
                      $brandQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Brand filter - handle both single and multiple brands
        if ($request->has('brand')) {
            $brands = is_array($request->get('brand')) ? $request->get('brand') : [$request->get('brand')];
            $brands = array_filter($brands); // Remove empty values
            
            if (!empty($brands)) {
                $query->whereHas('brand', function ($q) use ($brands) {
                    $q->whereIn('slug', $brands);
                });
            }
        }
        
        // Category filter - handle both single and multiple categories
        if ($request->has('category')) {
            $categories = is_array($request->get('category')) ? $request->get('category') : [$request->get('category')];
            $categories = array_filter($categories); // Remove empty values
            
            if (!empty($categories)) {
                $query->whereHas('subcategory.category', function ($q) use ($categories) {
                    $q->whereIn('slug', $categories);
                });
            }
        }
        
        // Subcategory filter - handle both single and multiple subcategories
        if ($request->has('subcategory')) {
            $subcategories = is_array($request->get('subcategory')) ? $request->get('subcategory') : [$request->get('subcategory')];
            $subcategories = array_filter($subcategories); // Remove empty values
            
            if (!empty($subcategories)) {
                $query->whereHas('subcategory', function ($q) use ($subcategories) {
                    $q->whereIn('slug', $subcategories);
                });
            }
        }
        
        // Price range filter (using product price)
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->get('min_price'));
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->get('max_price'));
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    public function show($slug)
    {
        $product = Product::with([
            'brand',
            'defaultColor',
            'subcategory.category', 
            'sizes',
            'variants.color',
            'variants.size',
            'variants.images' => function($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('sort_order', 'asc');
            },
            'images' => function($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('sort_order', 'asc');
            }
        ])->where('slug', $slug)->first();

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        // Check if product is active - return 404 if inactive for public access
        if (!$product->is_active) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json($product);
    }

    /**
     * Get related products from the same subcategory
     * Excludes the current product and ensures no duplicates
     */
    public function getRelatedProducts($slug)
    {
        $product = Product::where('slug', $slug)->first();

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        // Get related products from the same subcategory
        $relatedProducts = Product::with([
            'brand',
            'images' => function($query) {
                $query->orderBy('is_primary', 'desc')
                      ->orderBy('sort_order', 'asc')
                      ->limit(1); // Only get primary image
            }
        ])
        ->where('subcategory_id', $product->subcategory_id)
        ->where('id', '!=', $product->id) // Exclude current product
        ->where('is_active', true) // Only active products
        ->distinct() // Ensure no duplicates at database level
        ->limit(12) // Limit to 12 products
        ->get()
        ->unique('id') // Additional deduplication by ID
        ->unique('slug') // Additional deduplication by slug
        ->values(); // Re-index array

        return response()->json($relatedProducts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subcategory_id'    => 'required|integer|exists:subcategories,id',

            // Brand
            'brand_id'          => 'nullable|integer|exists:brands,id',
            'brand_name'        => 'nullable|string|max:255',

            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'meta_title'        => 'nullable|string|max:255',
            'meta_description'  => 'nullable|string|max:500',
            'meta_keywords'     => 'nullable|string',
            'canonical_url'     => 'nullable|url',
            'robots'            => 'nullable|string|max:50',
            'is_active'         => 'boolean',
            'is_featured'       => 'boolean',
            'is_hot'            => 'boolean',
            'is_new'            => 'boolean',
            
            // Product price (for products without variants)
            'base_price'        => 'nullable|numeric|min:0',
            'stock'             => 'nullable|integer|min:0',
            'sku'               => 'nullable|string|max:50|unique:products,sku',
            'size_stocks'       => 'nullable|array', // Stock per size for products without color variants
            'size_stocks.*'     => 'integer|min:0',
            
            // Default color
            'default_color_id'   => 'nullable|integer|exists:colors,id',
            'default_color_name' => 'nullable|string|max:100',
            'default_color_hex'  => 'nullable|string|max:7',
            
            // Sizes
            'sizes'             => 'nullable|array',
            'sizes.*'           => 'integer|exists:sizes,id',

            // Variants
            'variants'                  => 'nullable|array',
            'variants.*.color_name'     => 'required_with:variants|string|max:100',
            'variants.*.color_hex'      => 'required_with:variants|string|max:7', // "#RRGGBB"
            'variants.*.available_sizes' => 'nullable|array',
            'variants.*.available_sizes.*' => 'integer|exists:sizes,id',
            'variants.*.stock'          => 'required_with:variants|integer|min:0',
            'variants.*.sku'            => 'required_with:variants|string|max:100',
            'variants.*.size_stocks'    => 'nullable|array',
            'variants.*.size_stocks.*'  => 'integer|min:0',

            // Images
            'images'                    => 'nullable|array',
            'images.*'                  => 'image|mimes:jpeg,png,jpg,webp|max:2048',

            // Variant images
            'variants.*.images'         => 'nullable|array',
            'variants.*.images.*'       => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        DB::beginTransaction();

        try {
            // Handle brand
            if (!empty($validated['brand_id'])) {
                $brandId = $validated['brand_id'];
            } elseif (!empty($validated['brand_name'])) {
                $brand = Brand::firstOrCreate(
                    ['name' => $validated['brand_name']],
                    ['slug' => Str::slug($validated['brand_name'])]
                );
                $brandId = $brand->id;
            } else {
                return response()->json([
                    'message' => 'Brand is required (either brand_id or brand_name)',
                ], 422);
            }

            // Handle default color
            $defaultColorId = null;
            if (!empty($validated['default_color_id'])) {
                $defaultColorId = $validated['default_color_id'];
            } elseif (!empty($validated['default_color_name']) && !empty($validated['default_color_hex'])) {
                $color = Color::firstOrCreate(
                    [
                        'name' => $validated['default_color_name'],
                        'hex_code' => $validated['default_color_hex']
                    ]
                );
                $defaultColorId = $color->id;
            }

            // Set product base_price if provided (variants will inherit from product)
            $productBasePrice = null;
            if (!empty($validated['base_price']) && $validated['base_price'] > 0) {
                $productBasePrice = $validated['base_price'];
            }

            // Generate unique slug
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (Product::where('slug', $slug)->withTrashed()->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            // Create product
            $product = Product::create([
                'subcategory_id'   => $validated['subcategory_id'],
                'brand_id'         => $brandId,
                'default_color_id' => $defaultColorId,
                'name'             => $validated['name'],
                'slug'             => $slug,
                'description'      => $validated['description'] ?? null,
                'meta_title'       => $validated['meta_title'] ?? null,
                'meta_description' => $validated['meta_description'] ?? null,
                'meta_keywords'    => $validated['meta_keywords'] ?? null,
                'canonical_url'    => $validated['canonical_url'] ?? null,
                'robots'           => $validated['robots'] ?? 'index,follow',
                'is_active'        => $validated['is_active'] ?? true,
                'is_featured'      => $validated['is_featured'] ?? false,
                'is_hot'           => $validated['is_hot'] ?? false,
                'is_new'           => $validated['is_new'] ?? false,
                'base_price'       => $productBasePrice,
                'stock'            => $validated['stock'] ?? 0,
                'sku'              => $validated['sku'] ?? null,
            ]);

            // Product-level images
            if (!empty($validated['images'])) {
                foreach ($validated['images'] as $index => $image) {
                    $path = $image->store('products', 'public');
                    $product->images()->create([
                        'url'        => $path,
                        'is_primary' => $index === 0,
                        'sort_order' => $index,
                    ]);
                }
            }

            // Attach sizes to product
            if (!empty($validated['sizes'])) {
                $product->sizes()->attach($validated['sizes']);
            }

            // If product has sizes but no color variants, create size-only variants
            if (!empty($validated['sizes']) && empty($validated['variants'])) {
                foreach ($validated['sizes'] as $sizeId) {
                    // Use stock from size_stocks if provided, otherwise use product stock
                    $stock = $validated['size_stocks'][$sizeId] ?? ($validated['stock'] ?? 0);
                    
                    // Create variant with size but no color (for products with sizes but no color variants)
                    $product->variants()->create([
                        'color_id'  => $defaultColorId, // Use default color if available, otherwise null
                        'size_id'   => $sizeId,
                        'stock'     => $stock,
                        'sku'       => (!empty($validated['sku'])) ? ($validated['sku'] . '-' . $sizeId) : null,
                    ]);
                }
            }

            // Variants (color variants)
            if (!empty($validated['variants'])) {
                foreach ($validated['variants'] as $variantData) {
                    // Handle color: check existing by name & hex first
                $color = Color::where('name', $variantData['color_name'])
                              ->where('hex_code', $variantData['color_hex'])
                              ->first();

                if (!$color) {
                    $color = Color::create([
                        'name'     => $variantData['color_name'],
                        'hex_code' => $variantData['color_hex'],
                    ]);
                }
                $colorId = $color->id;

                // Create variants for each available size, or a single variant if no sizes
                if (!empty($variantData['available_sizes'])) {
                    // Create individual variants for each available size
                    foreach ($variantData['available_sizes'] as $sizeId) {
                        // Use individual stock per size if provided, otherwise use the general stock
                        $stock = $variantData['size_stocks'][$sizeId] ?? $variantData['stock'];
                        
                        $variant = $product->variants()->create([
                            'color_id'  => $colorId,
                            'size_id'   => $sizeId,
                            'stock'     => $stock,
                            'sku'       => $variantData['sku'] . '-' . $sizeId, // Unique SKU per size
                        ]);

                        // Variant images for this specific size variant
                        if (!empty($variantData['images'])) {
                            foreach ($variantData['images'] as $i => $variantImage) {
                                $path = $variantImage->store('variants', 'public');
                                $variant->images()->create([
                                    'product_id' => $product->id,
                                    'url'        => $path,
                                    'is_primary' => $i === 0,
                                    'sort_order' => $i,
                                ]);
                            }
                        }
                    }
                } else {
                    // Create a single variant without size when no sizes are selected
                    $variant = $product->variants()->create([
                        'color_id'   => $colorId,
                        'size_id'    => null, // No size specified
                        'stock'      => $variantData['stock'],
                        'sku'        => $variantData['sku'],
                    ]);

                    // Variant images for this variant
                    if (!empty($variantData['images'])) {
                        foreach ($variantData['images'] as $i => $variantImage) {
                            $path = $variantImage->store('variants', 'public');
                            $variant->images()->create([
                                'product_id' => $product->id,
                                'url'        => $path,
                                'is_primary' => $i === 0,
                                'sort_order' => $i,
                            ]);
                        }
                    }
                }
            }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product->load('brand', 'defaultColor', 'subcategory.category', 'sizes', 'variants.color', 'variants.size', 'variants.images', 'images'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $slug)
    {
        try {
            $product = Product::where('slug', $slug)->firstOrFail();

            $validated = $request->validate([
                'name'              => 'sometimes|string|max:255',
                'description'       => 'nullable|string',
                'meta_title'        => 'nullable|string|max:255',
                'meta_description'  => 'nullable|string|max:500',
                'meta_keywords'     => 'nullable|string',
                'canonical_url'     => 'nullable|url',
                'robots'            => 'nullable|string|max:50',
                'is_active'         => 'boolean',
                'is_featured'       => 'boolean',
                'is_hot'            => 'boolean',
                'is_new'            => 'boolean',
                'subcategory_id'    => 'sometimes|integer|exists:subcategories,id',
                'brand_id'          => 'nullable|integer|exists:brands,id',
                
                // Product price
                'base_price'        => 'nullable|numeric|min:0',
                'stock'             => 'nullable|integer|min:0',
                'sku'               => 'nullable|string|max:50|unique:products,sku,' . $product->id,
                
                // Default color
                'default_color_id'   => 'nullable|integer|exists:colors,id',
                'default_color_name' => 'nullable|string|max:100',
                'default_color_hex'  => 'nullable|string|max:7',
                
                // Sizes
                'sizes'             => 'nullable|array',
                'sizes.*'           => 'integer|exists:sizes,id',
                
                // Product Images
                'existing_image_ids' => 'nullable|array',
                'existing_image_ids.*' => 'integer|exists:product_images,id',
                'images_to_delete'   => 'nullable|array',
                'images_to_delete.*' => 'integer|exists:product_images,id',
                'new_images'         => 'nullable|array',
                'new_images.*'       => 'image|mimes:jpeg,png,jpg,webp|max:2048',
                'primary_existing_image_id' => 'nullable|integer|exists:product_images,id',
                'primary_new_image_index'   => 'nullable|integer|min:0',
                
                // Variants
                'variants'                  => 'nullable|array',
                'variants.*.id'             => 'nullable|integer|exists:product_variants,id',
                'variants.*.color_id'       => 'nullable|integer|exists:colors,id',
                'variants.*.color_name'     => 'nullable|string|max:100',
                'variants.*.color_hex'      => 'nullable|string|max:7',
                'variants.*.available_sizes' => 'nullable|array',
                'variants.*.available_sizes.*' => 'integer|exists:sizes,id',
                'variants.*.stock'          => 'nullable|integer|min:0',
                'variants.*.sku'            => 'nullable|string|max:100',
                'variants.*.size_stocks'    => 'nullable|array',
                'variants.*.size_stocks.*'  => 'integer|min:0',
                'variants.*.existing_images' => 'nullable|array',
                'variants.*.existing_images.*' => 'integer|exists:product_images,id',
                'variants.*.images_to_delete' => 'nullable|array',
                'variants.*.images_to_delete.*' => 'integer|exists:product_images,id',
                'variants.*.new_images'     => 'nullable|array',
                'variants.*.new_images.*'   => 'image|mimes:jpeg,png,jpg,webp|max:2048',
                'variants.*.primary_existing_image_id' => 'nullable|integer|exists:product_images,id',
                'variants.*.primary_new_image_index'   => 'nullable|integer|min:0',
            ]);

            DB::beginTransaction();

            // Update slug if name changed
            if (isset($validated['name']) && $validated['name'] !== $product->name) {
                $baseSlug = Str::slug($validated['name']);
                $slug = $baseSlug;
                $counter = 1;
                // Check for uniqueness excluding current product
                while (Product::where('slug', $slug)->where('id', '!=', $product->id)->withTrashed()->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                $validated['slug'] = $slug;
            }

            // Handle default color
            if (isset($validated['default_color_id'])) {
                $validated['default_color_id'] = $validated['default_color_id'];
            } elseif (isset($validated['default_color_name']) && isset($validated['default_color_hex'])) {
                $color = Color::firstOrCreate(
                    [
                        'name' => $validated['default_color_name'],
                        'hex_code' => $validated['default_color_hex']
                    ]
                );
                $validated['default_color_id'] = $color->id;
            }

            // Update product basic fields
            $product->update(array_filter($validated, function($key) {
                return !in_array($key, ['sizes', 'existing_image_ids', 'images_to_delete', 'new_images', 
                                         'primary_existing_image_id', 'primary_new_image_index', 'variants',
                                         'default_color_name', 'default_color_hex']);
            }, ARRAY_FILTER_USE_KEY));

            // Handle product images
            // Delete images
            if (!empty($validated['images_to_delete'])) {
                foreach ($validated['images_to_delete'] as $imageId) {
                    $image = $product->images()->where('id', $imageId)->whereNull('variant_id')->first();
                    if ($image) {
                        // Delete file from storage
                        if (Storage::disk('public')->exists($image->url)) {
                            Storage::disk('public')->delete($image->url);
                        }
                        $image->delete();
                    }
                }
            }

            // Add new images
            if (!empty($validated['new_images'])) {
                $existingImageCount = $product->images()->whereNull('variant_id')->count();
                foreach ($validated['new_images'] as $index => $image) {
                    $path = $image->store('products', 'public');
                    $product->images()->create([
                        'url'        => $path,
                        'is_primary' => false, // Will be set below
                        'sort_order' => $existingImageCount + $index,
                    ]);
                }
            }

            // Set primary image
            if (isset($validated['primary_existing_image_id'])) {
                // Unset all existing primary images
                $product->images()->whereNull('variant_id')->update(['is_primary' => false]);
                // Set the selected existing image as primary
                $product->images()->where('id', $validated['primary_existing_image_id'])
                    ->whereNull('variant_id')->update(['is_primary' => true]);
            } elseif (isset($validated['primary_new_image_index']) && !empty($validated['new_images'])) {
                // Unset all existing primary images
                $product->images()->whereNull('variant_id')->update(['is_primary' => false]);
                // Find the newly uploaded image and set it as primary
                $newImages = $product->images()->whereNull('variant_id')
                    ->orderBy('sort_order', 'desc')
                    ->take(count($validated['new_images']))
                    ->get();
                if (isset($newImages[$validated['primary_new_image_index']])) {
                    $newImages[$validated['primary_new_image_index']]->update(['is_primary' => true]);
                }
            }

            // Update sizes
            if (isset($validated['sizes'])) {
                $product->sizes()->sync($validated['sizes']);
            }

            // Handle variants
            if (isset($validated['variants'])) {
                // Get existing variant IDs
                $existingVariantIds = $product->variants()->pluck('id')->toArray();
                $submittedVariantIds = array_filter(array_column($validated['variants'], 'id'));
                
                // Delete variants that are no longer in the submitted list
                $variantsToDelete = array_diff($existingVariantIds, $submittedVariantIds);
                foreach ($variantsToDelete as $variantId) {
                    $variant = $product->variants()->find($variantId);
                    if ($variant) {
                        // Delete variant images
                        foreach ($variant->images as $image) {
                            if (Storage::disk('public')->exists($image->url)) {
                                Storage::disk('public')->delete($image->url);
                            }
                        }
                        $variant->delete();
                    }
                }

                // Update or create variants
                foreach ($validated['variants'] as $variantData) {
                    // Handle color
                    $colorId = null;
                    if (!empty($variantData['color_id'])) {
                        $colorId = $variantData['color_id'];
                    } elseif (!empty($variantData['color_name']) && !empty($variantData['color_hex'])) {
                        $color = Color::where('name', $variantData['color_name'])
                                      ->where('hex_code', $variantData['color_hex'])
                                      ->first();
                        if (!$color) {
                            $color = Color::create([
                                'name'     => $variantData['color_name'],
                                'hex_code' => $variantData['color_hex'],
                            ]);
                        }
                        $colorId = $color->id;
                    }

                    if (!$colorId) continue;

                    // Update existing variant or create new
                    if (!empty($variantData['id'])) {
                        // Update existing variant
                        $variant = $product->variants()->find($variantData['id']);
                        if ($variant) {
                            $variant->update([
                                'color_id' => $colorId,
                                'stock'    => $variantData['stock'] ?? $variant->stock,
                                'sku'      => $variantData['sku'] ?? $variant->sku,
                            ]);

                            // Handle variant images
                            // Delete images
                            if (!empty($variantData['images_to_delete'])) {
                                foreach ($variantData['images_to_delete'] as $imageId) {
                                    $image = $variant->images()->where('id', $imageId)->first();
                                    if ($image) {
                                        if (Storage::disk('public')->exists($image->url)) {
                                            Storage::disk('public')->delete($image->url);
                                        }
                                        $image->delete();
                                    }
                                }
                            }

                            // Add new images
                            if (!empty($variantData['new_images'])) {
                                $existingImageCount = $variant->images()->count();
                                foreach ($variantData['new_images'] as $index => $image) {
                                    $path = $image->store('variants', 'public');
                                    $variant->images()->create([
                                        'product_id' => $product->id,
                                        'url'        => $path,
                                        'is_primary' => false,
                                        'sort_order' => $existingImageCount + $index,
                                    ]);
                                }
                            }

                            // Set primary image
                            if (isset($variantData['primary_existing_image_id'])) {
                                $variant->images()->update(['is_primary' => false]);
                                $variant->images()->where('id', $variantData['primary_existing_image_id'])
                                    ->update(['is_primary' => true]);
                            } elseif (isset($variantData['primary_new_image_index']) && !empty($variantData['new_images'])) {
                                $variant->images()->update(['is_primary' => false]);
                                $newImages = $variant->images()->orderBy('sort_order', 'desc')
                                    ->take(count($variantData['new_images']))
                                    ->get();
                                if (isset($newImages[$variantData['primary_new_image_index']])) {
                                    $newImages[$variantData['primary_new_image_index']]->update(['is_primary' => true]);
                                }
                            }

                            // Update size-specific variants if sizes changed
                            if (!empty($variantData['available_sizes'])) {
                                // This is complex - we need to handle size variants properly
                                // For now, we'll update the existing variant's size if it's a single size variant
                                // Multi-size variants would need more complex logic
                            }
                        }
                    } else {
                        // Create new variant (similar to store method)
                        if (!empty($variantData['available_sizes'])) {
                            foreach ($variantData['available_sizes'] as $sizeId) {
                                $stock = $variantData['size_stocks'][$sizeId] ?? ($variantData['stock'] ?? 0);
                                $variant = $product->variants()->create([
                                    'color_id'  => $colorId,
                                    'size_id'   => $sizeId,
                                    'stock'     => $stock,
                                    'sku'       => ($variantData['sku'] ?? '') . '-' . $sizeId,
                                ]);

                                // Variant images
                                if (!empty($variantData['new_images'])) {
                                    foreach ($variantData['new_images'] as $i => $variantImage) {
                                        $path = $variantImage->store('variants', 'public');
                                        $variant->images()->create([
                                            'product_id' => $product->id,
                                            'url'        => $path,
                                            'is_primary' => $i === 0,
                                            'sort_order' => $i,
                                        ]);
                                    }
                                }
                            }
                        } else {
                            $variant = $product->variants()->create([
                                'color_id'   => $colorId,
                                'size_id'    => null,
                                'stock'      => $variantData['stock'] ?? 0,
                                'sku'        => $variantData['sku'] ?? '',
                            ]);

                            // Variant images
                            if (!empty($variantData['new_images'])) {
                                foreach ($variantData['new_images'] as $i => $variantImage) {
                                    $path = $variantImage->store('variants', 'public');
                                    $variant->images()->create([
                                        'product_id' => $product->id,
                                        'url'        => $path,
                                        'is_primary' => $i === 0,
                                        'sort_order' => $i,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product->load('brand', 'defaultColor', 'subcategory.category', 'sizes', 'variants.color', 'variants.size', 'variants.images', 'images'),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkUpdateLabels(Request $request)
    {
        try {
            $validated = $request->validate([
                'products' => 'required|array',
                'products.*.id' => 'required|integer|exists:products,id',
                'products.*.is_featured' => 'boolean',
                'products.*.is_hot' => 'boolean',
                'products.*.is_new' => 'boolean',
            ]);

            DB::beginTransaction();

            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['id']);
                if ($product) {
                    $updateData = [];
                    if (isset($productData['is_featured'])) {
                        $updateData['is_featured'] = $productData['is_featured'];
                    }
                    if (isset($productData['is_hot'])) {
                        $updateData['is_hot'] = $productData['is_hot'];
                    }
                    if (isset($productData['is_new'])) {
                        $updateData['is_new'] = $productData['is_new'];
                    }
                    $product->update($updateData);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product labels updated successfully',
                'updated_count' => count($validated['products']),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update product labels',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkUpdateActiveStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_ids' => 'required|array',
                'product_ids.*' => 'required|integer|exists:products,id',
                'is_active' => 'required|boolean',
            ]);

            DB::beginTransaction();

            $updatedCount = Product::whereIn('id', $validated['product_ids'])
                ->update(['is_active' => $validated['is_active']]);

            DB::commit();

            return response()->json([
                'message' => 'Product status updated successfully',
                'updated_count' => $updatedCount,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update product status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($slug)
    {
        try {
            $product = Product::where('slug', $slug)->firstOrFail();
            
            DB::beginTransaction();

            // Delete product images from storage
            foreach ($product->images as $image) {
                if ($image->url && \Storage::disk('public')->exists($image->url)) {
                    \Storage::disk('public')->delete($image->url);
                }
                $image->delete();
            }

            // Delete variant images from storage
            foreach ($product->variants as $variant) {
                foreach ($variant->images as $image) {
                    if ($image->url && \Storage::disk('public')->exists($image->url)) {
                        \Storage::disk('public')->delete($image->url);
                    }
                    $image->delete();
                }
                $variant->delete();
            }

            // Detach sizes
            $product->sizes()->detach();

            // Delete product
            $product->delete();

            DB::commit();

            return response()->json([
                'message' => 'Product deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFeaturedProducts()
    {
        try {
            $products = Product::select('products.id', 'products.name', 'products.slug', 'products.subcategory_id')
                ->with([
                    'images' => function($query) {
                        $query->select('id', 'product_id', 'url', 'is_primary')
                            ->where('is_primary', true)
                            ->limit(1);
                    },
                    'subcategory:id,name,slug,category_id',
                    'subcategory.category:id,name,slug'
                ])
                ->where('is_featured', true)
                ->where('is_active', true)
                ->limit(3)
                ->get()
                ->map(function($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'image' => $product->images->first()?->url ?? null,
                        'category_slug' => $product->subcategory->category->slug ?? null,
                        'subcategory_slug' => $product->subcategory->slug ?? null,
                    ];
                });

            return response()->json(['data' => $products], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch featured products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTrendingProducts()
    {
        try {
            $products = Product::select('products.id', 'products.name', 'products.slug', 'products.price', 'products.brand_id', 'products.subcategory_id')
                ->with([
                    'images' => function($query) {
                        $query->select('id', 'product_id', 'url', 'is_primary')
                            ->where('is_primary', true)
                            ->limit(1);
                    },
                    'variants' => function($query) {
                        $query->select('id', 'product_id');
                    },
                    'brand:id,name',
                    'subcategory:id,name,slug,category_id',
                    'subcategory.category:id,name,slug'
                ])
                ->where('is_hot', true)
                ->where('is_active', true)
                ->limit(4)
                ->get()
                ->map(function($product) {
                    // Calculate average rating from all variants of this product
                    $variantIds = $product->variants->pluck('id')->toArray();
                    $ratingStats = null;
                    if (!empty($variantIds)) {
                        $ratingStats = Rating::whereIn('variant_id', $variantIds)
                            ->selectRaw('AVG(stars) as average_rating, COUNT(*) as total_ratings')
                            ->first();
                    }

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'price' => $product->price ?? 0,
                        'rating' => $ratingStats ? round((float)$ratingStats->average_rating, 1) : 0,
                        'total_ratings' => $ratingStats ? (int)$ratingStats->total_ratings : 0,
                        'image' => $product->images->first()?->url ?? null,
                        'brand' => $product->brand ? ['name' => $product->brand->name] : null,
                        'category_slug' => $product->subcategory->category->slug ?? null,
                        'subcategory_slug' => $product->subcategory->slug ?? null,
                    ];
                });

            return response()->json(['data' => $products], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch trending products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getNewArrivals()
    {
        try {
            $products = Product::select('products.id', 'products.name', 'products.slug', 'products.price', 'products.subcategory_id')
                ->with([
                    'images' => function($query) {
                        $query->select('id', 'product_id', 'url', 'is_primary')
                            ->where('is_primary', true)
                            ->limit(1);
                    },
                    'variants' => function($query) {
                        $query->select('id', 'product_id')
                            ->limit(1);
                    },
                    'subcategory:id,name,slug,category_id',
                    'subcategory.category:id,name,slug'
                ])
                ->where('is_new', true)
                ->where('is_active', true)
                ->limit(5)
                ->get()
                ->map(function($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'price' => $product->price ?? 0,
                        'image' => $product->images->first()?->url ?? null,
                        'category_slug' => $product->subcategory->category->slug ?? null,
                        'subcategory_slug' => $product->subcategory->slug ?? null,
                    ];
                });

            return response()->json(['data' => $products], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch new arrivals',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
