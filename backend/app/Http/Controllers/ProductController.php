<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Size;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        // Add color_id to each size based on default color
        if ($product->sizes && $product->default_color_id) {
            $product->sizes->transform(function ($size) use ($product) {
                $size->color_id = $product->default_color_id;
                return $size;
            });
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
            
            // Default color (required)
            'default_color_id'   => 'nullable|integer|exists:colors,id',
            'default_color_name' => 'required_without:default_color_id|string|max:100',
            'default_color_hex'  => 'required_without:default_color_id|string|max:7',
            
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

            // Handle default color (required)
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
            } else {
                return response()->json([
                    'message' => 'Default color is required (either default_color_id or default_color_name with default_color_hex)',
                ], 422);
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

            // Handle variant creation based on different scenarios:
            // 1. Default color only, no size: Create single variant with default color, no size
            // 2. Default color only, with size: Create variants with default color for each size
            // 3. Color variant, no size: Create variants for those colors (no size), and also default color (no size)
            // 4. Color variant and size: Create variants for those colors with sizes, and also default color with sizes

            // First, create variants for user-specified colors (if any)
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
                        // Upload images once (to first variant) to avoid duplicates
                        $uploadedImagePaths = [];
                        $firstVariantForImages = null;
                        
                        // Create individual variants for each available size
                        foreach ($variantData['available_sizes'] as $sizeId) {
                            // Use individual stock per size if provided, otherwise use the general stock
                            // Handle both string and integer keys (FormData sends string keys)
                            $stock = $variantData['size_stocks'][$sizeId] 
                                  ?? $variantData['size_stocks'][(string)$sizeId] 
                                  ?? $variantData['stock'];
                            
                            $variant = $product->variants()->create([
                                'color_id'  => $colorId,
                                'size_id'   => $sizeId,
                                'stock'     => $stock,
                                'sku'       => $variantData['sku'] . '-' . $sizeId, // Unique SKU per size
                            ]);

                            // Upload images only for the first variant, then reuse paths for others
                            if (!empty($variantData['images'])) {
                                if ($firstVariantForImages === null) {
                                    // First variant: upload images
                                    $firstVariantForImages = $variant;
                                    foreach ($variantData['images'] as $i => $variantImage) {
                                        $path = $variantImage->store('variants', 'public');
                                        $uploadedImagePaths[] = [
                                            'path' => $path,
                                            'is_primary' => $i === 0,
                                            'sort_order' => $i,
                                        ];
                                        $variant->images()->create([
                                            'product_id' => $product->id,
                                            'url'        => $path,
                                            'is_primary' => $i === 0,
                                            'sort_order' => $i,
                                        ]);
                                    }
                                } else {
                                    // Subsequent variants: reuse the same uploaded image paths
                                    foreach ($uploadedImagePaths as $imgData) {
                                        $variant->images()->create([
                                            'product_id' => $product->id,
                                            'url'        => $imgData['path'],
                                            'is_primary' => $imgData['is_primary'],
                                            'sort_order' => $imgData['sort_order'],
                                        ]);
                                    }
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

            // Now handle default color variants
            // Check if default color already has variants (from user-specified colors above)
            $defaultColorHasVariants = $product->variants()
                ->where('color_id', $defaultColorId)
                ->exists();
            
            // If default color doesn't have variants yet, create them
            if (!$defaultColorHasVariants) {
                if (!empty($validated['sizes'])) {
                    // Scenario 2 & 4: Default color with sizes
                    foreach ($validated['sizes'] as $sizeId) {
                        // Use stock from size_stocks if provided, otherwise use product stock
                        // Handle both string and integer keys (FormData sends string keys)
                        $stock = $validated['size_stocks'][$sizeId] 
                              ?? $validated['size_stocks'][(string)$sizeId] 
                              ?? ($validated['stock'] ?? 0);
                        
                        // Create variant for default color with size
                        $product->variants()->create([
                            'color_id'  => $defaultColorId,
                            'size_id'   => $sizeId,
                            'stock'     => $stock,
                            'sku'       => (!empty($validated['sku'])) ? ($validated['sku'] . '-' . $sizeId) : null,
                        ]);
                    }
                } else {
                    // Scenario 1 & 3: Default color without sizes
                    $product->variants()->create([
                        'color_id'  => $defaultColorId,
                        'size_id'   => null,
                        'stock'     => $validated['stock'] ?? 0,
                        'sku'       => $validated['sku'] ?? null,
                    ]);
                }
            }

            DB::commit();

            // Load product with relationships
            $product = $product->load('brand', 'defaultColor', 'subcategory.category', 'sizes', 'variants.color', 'variants.size', 'variants.images', 'images');
            
            // Add color_id to each size based on default color
            if ($product->sizes && $product->default_color_id) {
                $product->sizes->transform(function ($size) use ($product) {
                    $size->color_id = $product->default_color_id;
                    return $size;
                });
            }

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product,
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
            
            // LOG: Raw request data
            Log::info('🔴 BACKEND: Update request received', [
                'slug' => $slug,
                'product_id' => $product->id,
                'has_variants' => $request->has('variants'),
                'variants_count' => is_array($request->input('variants')) ? count($request->input('variants')) : 0,
                'raw_variants' => $request->input('variants')
            ]);

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
                'size_stocks'       => 'nullable|array', // Stock per size for products without color variants
                'size_stocks.*'     => 'integer|min:0',
                
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
                'variants.*.variant_ids'   => 'nullable|array',
                'variants.*.variant_ids.*' => 'integer|exists:product_variants,id',
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
                'variants.*.images_to_delete.*' => 'integer',
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
            // Get default color ID to preserve its variants
            $defaultColorId = null;
            if (!empty($validated['default_color_id'])) {
                $defaultColorId = $validated['default_color_id'];
            } elseif (!empty($validated['default_color_name']) && !empty($validated['default_color_hex'])) {
                $defaultColor = \App\Models\Color::where('name', $validated['default_color_name'])
                    ->where('hex_code', $validated['default_color_hex'])
                    ->first();
                if ($defaultColor) {
                    $defaultColorId = $defaultColor->id;
                }
            }
            
            if (isset($validated['variants'])) {
                // Get existing variant IDs
                $existingVariantIds = $product->variants()->pluck('id')->toArray();
                
                // Collect all submitted variant IDs (from 'id' and 'variant_ids' arrays)
                $submittedVariantIds = [];
                foreach ($validated['variants'] as $variantData) {
                    if (!empty($variantData['id'])) {
                        $submittedVariantIds[] = $variantData['id'];
                    }
                    if (!empty($variantData['variant_ids']) && is_array($variantData['variant_ids'])) {
                        $submittedVariantIds = array_merge($submittedVariantIds, $variantData['variant_ids']);
                    }
                }
                $submittedVariantIds = array_unique(array_filter($submittedVariantIds));
                
                // Preserve default color variants - don't delete them
                if ($defaultColorId) {
                    $defaultColorVariantIds = $product->variants()
                        ->where('color_id', $defaultColorId)
                        ->pluck('id')
                        ->toArray();
                    $submittedVariantIds = array_merge($submittedVariantIds, $defaultColorVariantIds);
                }
                
                // Delete variants that are no longer in the submitted list (excluding default color variants)
                $variantsToDelete = array_diff($existingVariantIds, $submittedVariantIds);
                // Don't delete default color variants
                if ($defaultColorId) {
                    $variantsToDelete = array_filter($variantsToDelete, function($id) use ($product, $defaultColorId) {
                        $variant = $product->variants()->find($id);
                        return $variant && $variant->color_id != $defaultColorId;
                    });
                }
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
                Log::info('🔴 BACKEND: Starting variant processing', [
                    'variants_count' => count($validated['variants'] ?? [])
                ]);
                
                foreach ($validated['variants'] as $vIndex => $variantData) {
                    Log::info("🔴 BACKEND: Processing variant index {$vIndex}", [
                        'variant_data' => $variantData,
                        'has_images_to_delete' => isset($variantData['images_to_delete']),
                        'images_to_delete' => $variantData['images_to_delete'] ?? 'NOT SET'
                    ]);
                    
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

                    // Update existing variants or create new
                    // If variant_ids is provided, update all variants of this color
                    $variantsToUpdate = [];
                    if (!empty($variantData['variant_ids']) && is_array($variantData['variant_ids'])) {
                        // Update all variants with these IDs
                        foreach ($variantData['variant_ids'] as $vid) {
                            $v = $product->variants()->find($vid);
                            if ($v && $v->color_id == $colorId) {
                                $variantsToUpdate[] = $v;
                            }
                        }
                    } elseif (!empty($variantData['id'])) {
                        // Fallback to single variant ID
                        $v = $product->variants()->find($variantData['id']);
                        if ($v) {
                            $variantsToUpdate[] = $v;
                        }
                    }
                    
                    // Update all variants of this color
                    // Only add images to the first variant to avoid duplicates
                    $firstVariant = !empty($variantsToUpdate) ? $variantsToUpdate[0] : null;
                    $imageHandled = false;
                    
                    foreach ($variantsToUpdate as $variant) {
                        // Update size-specific stock if size_stocks is provided
                        $stockToUpdate = $variantData['stock'] ?? $variant->stock;
                        if (!empty($variantData['size_stocks']) && $variant->size_id) {
                            $sizeId = $variant->size_id;
                            $sizeStock = $variantData['size_stocks'][$sizeId] 
                                      ?? $variantData['size_stocks'][(string)$sizeId] 
                                      ?? null;
                            if ($sizeStock !== null) {
                                $stockToUpdate = $sizeStock;
                            }
                        }
                        
                        // Generate SKU with size suffix if variant has a size
                        $skuToUpdate = $variant->sku; // Keep existing SKU by default
                        if (!empty($variantData['sku'])) {
                            if ($variant->size_id) {
                                // Append size suffix for variants with sizes
                                $skuToUpdate = $variantData['sku'] . '-' . $variant->size_id;
                            } else {
                                // Use base SKU for variants without sizes
                                $skuToUpdate = $variantData['sku'];
                            }
                        }
                        
                        $variant->update([
                            'color_id' => $colorId,
                            'stock'    => $stockToUpdate,
                            'sku'      => $skuToUpdate,
                        ]);

                        // Handle variant images - only for the first variant to avoid duplicates
                        if ($variant === $firstVariant && !$imageHandled) {
                            // Get all variants of this color (used for image operations)
                            $allColorVariants = $product->variants()->where('color_id', $colorId)->get();
                            
                            // LOG: Received variant data
                            \Log::info('🔴 BACKEND: Received variant data for deletion', [
                                'variant_id' => $variant->id,
                                'color_id' => $colorId,
                                'images_to_delete' => $variantData['images_to_delete'] ?? 'NOT SET',
                                'images_to_delete_type' => gettype($variantData['images_to_delete'] ?? null),
                                'images_to_delete_empty_check' => empty($variantData['images_to_delete'] ?? []),
                                'is_array_check' => is_array($variantData['images_to_delete'] ?? null),
                                'full_variant_data' => $variantData
                            ]);
                            
                            // Delete images - delete all image records with the same URL across all variants of this color
                            if (!empty($variantData['images_to_delete']) && is_array($variantData['images_to_delete'])) {
                                \Log::info('🔴 BACKEND: Processing image deletion', [
                                    'image_ids_to_delete' => $variantData['images_to_delete'],
                                    'count' => count($variantData['images_to_delete'])
                                ]);
                                
                                foreach ($variantData['images_to_delete'] as $imageId) {
                                    if (empty($imageId)) {
                                        \Log::warning('🔴 BACKEND: Skipping empty image ID');
                                        continue;
                                    }
                                    
                                    \Log::info('🔴 BACKEND: Attempting to delete image', ['image_id' => $imageId]);
                                    
                                    // Search for the image across all variants of this color (not just first variant)
                                    // since images can be added to any variant
                                    $image = null;
                                    $imageUrl = null;
                                    
                                    foreach ($allColorVariants as $colorVariant) {
                                        $foundImage = $colorVariant->images()->where('id', $imageId)->first();
                                        if ($foundImage) {
                                            $image = $foundImage;
                                            $imageUrl = $foundImage->url;
                                            \Log::info('🔴 BACKEND: Found image to delete', [
                                                'image_id' => $imageId,
                                                'image_url' => $imageUrl,
                                                'variant_id' => $colorVariant->id
                                            ]);
                                            break;
                                        }
                                    }
                                    
                                    if (!$image) {
                                        \Log::error('🔴 BACKEND: Image not found for deletion', [
                                            'image_id' => $imageId,
                                            'searched_variants' => $allColorVariants->pluck('id')->toArray()
                                        ]);
                                    }
                                    
                                    if ($image && $imageUrl) {
                                        $fileDeleted = false;
                                        
                                        \Log::info('🔴 BACKEND: Deleting image records', [
                                            'image_id' => $imageId,
                                            'image_url' => $imageUrl,
                                            'variants_count' => $allColorVariants->count()
                                        ]);
                                        
                                        // Delete all image records with the same URL across all variants of this color
                                        $deletedCount = 0;
                                        foreach ($allColorVariants as $colorVariant) {
                                            $imagesToDelete = $colorVariant->images()->where('url', $imageUrl)->get();
                                            foreach ($imagesToDelete as $img) {
                                                $img->delete();
                                                $deletedCount++;
                                                \Log::info('🔴 BACKEND: Deleted image record', [
                                                    'image_id' => $img->id,
                                                    'variant_id' => $colorVariant->id,
                                                    'url' => $img->url
                                                ]);
                                            }
                                        }
                                        
                                        \Log::info('🔴 BACKEND: Deleted image records count', ['count' => $deletedCount]);
                                        
                                        // Delete the physical file only once (since all variants share the same file)
                                        if (!$fileDeleted && Storage::disk('public')->exists($imageUrl)) {
                                            Storage::disk('public')->delete($imageUrl);
                                            $fileDeleted = true;
                                            \Log::info('🔴 BACKEND: Deleted physical file', ['url' => $imageUrl]);
                                        } else {
                                            \Log::warning('🔴 BACKEND: Physical file not found or already deleted', [
                                                'url' => $imageUrl,
                                                'exists' => Storage::disk('public')->exists($imageUrl),
                                                'file_deleted_flag' => $fileDeleted
                                            ]);
                                        }
                                    }
                                }
                            } else {
                                \Log::warning('🔴 BACKEND: images_to_delete is empty or not an array', [
                                    'empty_check' => empty($variantData['images_to_delete'] ?? []),
                                    'is_array' => is_array($variantData['images_to_delete'] ?? null),
                                    'value' => $variantData['images_to_delete'] ?? 'NOT SET'
                                ]);
                            }

                            // Add new images and track their paths for primary image setting
                            // Upload images once to first variant, then replicate to all variants of same color
                            $newImagePaths = [];
                            $uploadedImageData = [];
                            if (!empty($variantData['new_images'])) {
                                $existingImageCount = $variant->images()->count();
                                foreach ($variantData['new_images'] as $index => $image) {
                                    $path = $image->store('variants', 'public');
                                    $newImagePaths[$index] = $path;
                                    
                                    // Store image data for replication (is_primary will be set later)
                                    $uploadedImageData[] = [
                                        'path' => $path,
                                        'sort_order' => $existingImageCount + $index,
                                    ];
                                    
                                    // Create image for first variant (is_primary will be set later if needed)
                                    $variant->images()->create([
                                        'product_id' => $product->id,
                                        'url'        => $path,
                                        'is_primary' => false,
                                        'sort_order' => $existingImageCount + $index,
                                    ]);
                                }
                                
                                // Replicate images to all other variants of the same color
                                foreach ($allColorVariants as $colorVariant) {
                                    if ($colorVariant->id !== $variant->id) {
                                        foreach ($uploadedImageData as $imgData) {
                                            $colorVariant->images()->create([
                                                'product_id' => $product->id,
                                                'url'        => $imgData['path'],
                                                'is_primary' => false,
                                                'sort_order' => $imgData['sort_order'],
                                            ]);
                                        }
                                    }
                                }
                            }

                            // Set primary image - set for all variants of this color
                            if (isset($variantData['primary_existing_image_id'])) {
                                // Get the image URL - search across all variants of this color
                                $primaryImage = null;
                                $primaryImageUrl = null;
                                
                                // Search for the image across all variants of this color
                                foreach ($allColorVariants as $colorVariant) {
                                    $foundImage = $colorVariant->images()->where('id', $variantData['primary_existing_image_id'])->first();
                                    if ($foundImage) {
                                        $primaryImage = $foundImage;
                                        $primaryImageUrl = $foundImage->url;
                                        break;
                                    }
                                }
                                
                                if ($primaryImageUrl) {
                                    // Set all images with this URL as primary, and others as not primary
                                    foreach ($allColorVariants as $colorVariant) {
                                        $colorVariant->images()->update(['is_primary' => false]);
                                        $colorVariant->images()->where('url', $primaryImageUrl)
                                            ->update(['is_primary' => true]);
                                    }
                                }
                            } elseif (isset($variantData['primary_new_image_index']) && $variantData['primary_new_image_index'] !== null && !empty($newImagePaths)) {
                                // Get the newly uploaded image path using the index
                                $newImageIndex = (int)$variantData['primary_new_image_index'];
                                if (isset($newImagePaths[$newImageIndex]) && !empty($newImagePaths[$newImageIndex])) {
                                    $newImageUrl = $newImagePaths[$newImageIndex];
                                    
                                    // Set all images as not primary first
                                    foreach ($allColorVariants as $colorVariant) {
                                        $colorVariant->images()->update(['is_primary' => false]);
                                    }
                                    
                                    // Set this image as primary for all variants of this color
                                    foreach ($allColorVariants as $colorVariant) {
                                        $colorVariant->images()->where('url', $newImageUrl)
                                            ->update(['is_primary' => true]);
                                    }
                                }
                            }
                            $imageHandled = true;
                        }
                    }
                    
                    // If no variants to update but we have available_sizes, create new variants
                    if (empty($variantsToUpdate) && !empty($variantData['available_sizes'])) {
                        // Upload images once (to first variant) to avoid duplicates
                        $uploadedImagePaths = [];
                        $firstVariantForImages = null;
                        
                        foreach ($variantData['available_sizes'] as $sizeId) {
                            // Handle both string and integer keys (FormData sends string keys)
                            $stock = $variantData['size_stocks'][$sizeId] 
                                  ?? $variantData['size_stocks'][(string)$sizeId] 
                                  ?? ($variantData['stock'] ?? 0);
                            $variant = $product->variants()->create([
                                'color_id'  => $colorId,
                                'size_id'   => $sizeId,
                                'stock'     => $stock,
                                'sku'       => ($variantData['sku'] ?? '') . '-' . $sizeId,
                            ]);

                            // Upload images only for the first variant, then reuse paths for others
                            if (!empty($variantData['new_images'])) {
                                if ($firstVariantForImages === null) {
                                    // First variant: upload images
                                    $firstVariantForImages = $variant;
                                    foreach ($variantData['new_images'] as $i => $variantImage) {
                                        $path = $variantImage->store('variants', 'public');
                                        $uploadedImagePaths[] = [
                                            'path' => $path,
                                            'is_primary' => $i === 0,
                                            'sort_order' => $i,
                                        ];
                                        $variant->images()->create([
                                            'product_id' => $product->id,
                                            'url'        => $path,
                                            'is_primary' => $i === 0,
                                            'sort_order' => $i,
                                        ]);
                                    }
                                } else {
                                    // Subsequent variants: reuse the same uploaded image paths
                                    foreach ($uploadedImagePaths as $imgData) {
                                        $variant->images()->create([
                                            'product_id' => $product->id,
                                            'url'        => $imgData['path'],
                                            'is_primary' => $imgData['is_primary'],
                                            'sort_order' => $imgData['sort_order'],
                                        ]);
                                    }
                                }
                            }
                        }
                    } else if (empty($variantsToUpdate)) {
                        // Create new variant without sizes
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

            // Handle default color variants - create or update them
            if (!empty($defaultColorId)) {
                // Update product's default_color_id
                $product->update(['default_color_id' => $defaultColorId]);
                
                if (!empty($validated['sizes'])) {
                    // Create or update default color variants for each size
                    foreach ($validated['sizes'] as $sizeId) {
                        // Check if variant already exists
                        $existingVariant = $product->variants()
                            ->where('color_id', $defaultColorId)
                            ->where('size_id', $sizeId)
                            ->first();
                        
                        // Use stock from size_stocks if provided, otherwise use product stock
                        // Handle both string and integer keys (FormData sends string keys)
                        $stock = $validated['size_stocks'][$sizeId] 
                              ?? $validated['size_stocks'][(string)$sizeId] 
                              ?? ($validated['stock'] ?? 0);
                        
                        if ($existingVariant) {
                            // Update existing variant
                            $existingVariant->update([
                                'stock' => $stock,
                                'sku'   => (!empty($validated['sku'])) ? ($validated['sku'] . '-' . $sizeId) : $existingVariant->sku,
                            ]);
                        } else {
                            // Create new variant for default color with size
                            $product->variants()->create([
                                'color_id' => $defaultColorId,
                                'size_id'  => $sizeId,
                                'stock'    => $stock,
                                'sku'      => (!empty($validated['sku'])) ? ($validated['sku'] . '-' . $sizeId) : null,
                            ]);
                        }
                    }
                } else {
                    // Default color without sizes
                    $existingVariant = $product->variants()
                        ->where('color_id', $defaultColorId)
                        ->whereNull('size_id')
                        ->first();
                    
                    if ($existingVariant) {
                        $existingVariant->update([
                            'stock' => $validated['stock'] ?? $existingVariant->stock,
                            'sku'   => $validated['sku'] ?? $existingVariant->sku,
                        ]);
                    } else {
                        $product->variants()->create([
                            'color_id' => $defaultColorId,
                            'size_id'  => null,
                            'stock'    => $validated['stock'] ?? 0,
                            'sku'      => $validated['sku'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            // Load product with relationships
            $product = $product->load('brand', 'defaultColor', 'subcategory.category', 'sizes', 'variants.color', 'variants.size', 'variants.images', 'images');
            
            // Add color_id to each size based on default color
            if ($product->sizes && $product->default_color_id) {
                $product->sizes->transform(function ($size) use ($product) {
                    $size->color_id = $product->default_color_id;
                    return $size;
                });
            }

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product,
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
