<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with([
            'brand',
            'subcategory.category', 
            'variants.color',
            'variants.size',
            'variants.images',
            'images'
        ]);

        // Optional filters
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
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
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('brand', function ($brandQuery) use ($search) {
                      $brandQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Brand filter
        if ($request->has('brand')) {
            $query->whereHas('brand', function ($q) use ($request) {
                $q->where('slug', $request->get('brand'));
            });
        }
        
        // Category filter
        if ($request->has('category')) {
            $query->whereHas('subcategory.category', function ($q) use ($request) {
                $q->where('slug', $request->get('category'));
            });
        }
        
        // Subcategory filter
        if ($request->has('subcategory')) {
            $query->whereHas('subcategory', function ($q) use ($request) {
                $q->where('slug', $request->get('subcategory'));
            });
        }
        
        // Price range filter
        if ($request->has('min_price')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('price', '>=', $request->get('min_price'));
            });
        }
        if ($request->has('max_price')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('price', '<=', $request->get('max_price'));
            });
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
            'subcategory.category', 
            'variants.color',
            'variants.size',
            'variants.images',
            'images'
        ])->where('slug', $slug)->first();

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }


        return response()->json($product);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subcategory_id'    => 'required|exists:subcategories,id',

            // Brand
            'brand_id'          => 'nullable|exists:brands,id',
            'brand_name'        => 'nullable|string|max:255',

            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'meta_title'        => 'nullable|string|max:255',
            'meta_description'  => 'nullable|string|max:500',
            'is_active'         => 'boolean',
            'is_featured'       => 'boolean',
            'is_hot'            => 'boolean',
            'is_new'            => 'boolean',

            // Variants
            'variants'                  => 'required|array',
            'variants.*.color_name'     => 'required|string|max:100',
            'variants.*.color_hex'      => 'required|string|max:7', // "#RRGGBB"
            'variants.*.size_name'      => 'required|string|max:50',
            'variants.*.price'          => 'required|numeric|min:0',
            'variants.*.stock'          => 'required|integer|min:0',
            'variants.*.sku'            => 'required|string|max:100',

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

            // Create product
            $product = Product::create([
                'subcategory_id'   => $validated['subcategory_id'],
                'brand_id'         => $brandId,
                'name'             => $validated['name'],
                'slug'             => Str::slug($validated['name']),
                'description'      => $validated['description'] ?? null,
                'meta_title'       => $validated['meta_title'] ?? null,
                'meta_description' => $validated['meta_description'] ?? null,
                'is_active'        => $validated['is_active'] ?? true,
                'is_featured'      => $validated['is_featured'] ?? false,
                'is_hot'           => $validated['is_hot'] ?? false,
                'is_new'           => $validated['is_new'] ?? false,
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

            // Variants
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

                // Handle size: check existing by name
                $size = Size::where('name', $variantData['size_name'])->first();
                if (!$size) {
                    $size = Size::create([
                        'name' => $variantData['size_name'],
                    ]);
                }
                $sizeId = $size->id;

                // Create variant
                $variant = $product->variants()->create([
                    'color_id' => $colorId,
                    'size_id'  => $sizeId,
                    'price'    => $variantData['price'],
                    'stock'    => $variantData['stock'],
                    'sku'      => $variantData['sku'],
                ]);

                // Variant images
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

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product->load('brand', 'variants.color', 'variants.size', 'images'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
