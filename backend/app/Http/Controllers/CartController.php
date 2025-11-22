<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $items = Cart::with([
            'variant.product',
            'variant.product.images' => function($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('sort_order', 'asc');
            },
            'variant.color',
            'variant.size',
            'variant.images' => function($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('sort_order', 'asc');
            }
        ])
            ->where('user_id', Auth::id())
            ->get();

        // Ensure variant images are populated: if a variant has no images, use product images
        foreach ($items as $item) {
            if ($item->variant && $item->variant->images && $item->variant->images->isEmpty()) {
                $variant = $item->variant;
                $product = $variant->product;
                
                // For default variants (null color_id), use all product images without variant_id
                if ($product && !$variant->color_id) {
                    $productImages = collect($product->images)->filter(function ($img) {
                        return !$img->variant_id;
                    })->values();
                    if ($productImages->isNotEmpty()) {
                        $variant->setRelation('images', $productImages);
                    }
                } elseif ($product && $variant->color_id) {
                    // For variants with color, use color-matched images
                    $variantIdsSameColor = ProductVariant::where('product_id', $product->id)
                        ->where('color_id', $variant->color_id)
                        ->pluck('id');

                    $colorImages = collect($product->images)->filter(function ($img) use ($variantIdsSameColor) {
                        return $img->variant_id && $variantIdsSameColor->contains($img->variant_id);
                    })->values();

                    if ($colorImages->isNotEmpty()) {
                        $variant->setRelation('images', $colorImages);
                    }
                }
            }
        }

        return response()->json($items);
    }

    // Add item to cart (or increase quantity if already exists)
    public function store(Request $request)
    {
        // Prevent admins from adding to cart
        if (Auth::user()->role === 'admin') {
            return response()->json([
                'message' => 'Administrators cannot add items to cart. Please use a customer account to make purchases.'
            ], 403);
        }

        $data = $request->validate([
            'variant_id' => 'required_without:product_id|exists:product_variants,id',
            'product_id' => 'required_without:variant_id|exists:products,id',
            'size_id'    => 'nullable|integer|exists:sizes,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $variant = null;
        $variantId = null;

        // Handle product without variants - auto-create default variant
        if (isset($data['product_id'])) {
            $product = Product::findOrFail($data['product_id']);

            // Check if product has variants
            if ($product->variants()->count() > 0) {
                return response()->json([
                    'message' => 'Product has variants. Please use variant_id instead.'
                ], 400);
            }

            // Check if product is out of stock
            if ($product->isOutOfStock()) {
                return response()->json([
                    'message' => 'This item is out of stock',
                    'stock' => 0
                ], 400);
            }

            // Check if size_id is provided and validate it's associated with the product
            $sizeId = isset($data['size_id']) ? $data['size_id'] : null;
            if ($sizeId && !$product->sizes()->where('sizes.id', $sizeId)->exists()) {
                return response()->json([
                    'message' => 'The selected size is not available for this product.'
                ], 400);
            }

            // Check if a variant already exists (product_id with null color_id and matching size_id)
            $existingVariant = ProductVariant::where('product_id', $product->id)
                ->whereNull('color_id')
                ->where(function($query) use ($sizeId) {
                    if ($sizeId) {
                        $query->where('size_id', $sizeId);
                    } else {
                        $query->whereNull('size_id');
                    }
                })
                ->first();

            if ($existingVariant) {
                $variant = $existingVariant;
                $variantId = $existingVariant->id;
            } else {
                // Create variant using product's stock and sku, with size_id if provided
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'color_id' => null,
                    'size_id' => $sizeId,
                    'stock' => $product->stock,
                    'sku' => $product->sku,
                ]);
                $variantId = $variant->id;
            }
        } else {
            // Handle product with variants
            $variant = ProductVariant::findOrFail($data['variant_id']);
            $variantId = $data['variant_id'];
        }

        // Check if variant is out of stock
        if ($variant->isOutOfStock()) {
            return response()->json([
                'message' => 'This item is out of stock',
                'stock' => 0
            ], 400);
        }

        // Check if item already exists
        $existingItem = Cart::where('user_id', Auth::id())
            ->where('variant_id', $variantId)
            ->first();

        // Calculate total quantity (existing + new)
        $totalQuantity = $existingItem ? $existingItem->quantity + $data['quantity'] : $data['quantity'];

        // Validate total quantity against available stock
        if (!$variant->hasStock($totalQuantity)) {
            return response()->json([
                'message' => 'Not enough stock available',
                'available_stock' => $variant->stock,
                'requested_quantity' => $totalQuantity
            ], 400);
        }

        if ($existingItem) {
            // Update existing item by adding quantity
            $existingItem->update([
                'quantity' => $totalQuantity
            ]);
            $cartItem = $existingItem;
        } else {
            // Create new item with exact quantity
            $cartItem = Cart::create([
                'user_id'   => Auth::id(),
                'variant_id'=> $variantId,
                'quantity'  => $data['quantity'],
            ]);
        }

        // Load the relationships for the response (include product images for color-based fallback)
        $cartItem->load('variant.product', 'variant.product.images', 'variant.color', 'variant.size', 'variant.images');

        // If the variant has no images, use product images
        if ($cartItem->variant && $cartItem->variant->images && $cartItem->variant->images->isEmpty()) {
            $variant = $cartItem->variant;
            $product = $variant->product;
            
            // For default variants (null color_id), use all product images without variant_id
            if ($product && !$variant->color_id) {
                $productImages = collect($product->images)->filter(function ($img) {
                    return !$img->variant_id;
                })->values();
                if ($productImages->isNotEmpty()) {
                    $variant->setRelation('images', $productImages);
                }
            } elseif ($product && $variant->color_id) {
                // For variants with color, use color-matched images
                $variantIdsSameColor = ProductVariant::where('product_id', $product->id)
                    ->where('color_id', $variant->color_id)
                    ->pluck('id');
                $colorImages = collect($product->images)->filter(function ($img) use ($variantIdsSameColor) {
                    return $img->variant_id && $variantIdsSameColor->contains($img->variant_id);
                })->values();
                if ($colorImages->isNotEmpty()) {
                    $variant->setRelation('images', $colorImages);
                }
            }
        }

        return response()->json($cartItem, 201);
    }

    // Update quantity of a specific cart item
    public function update(Request $request, Cart $cart)
    {
        if ($cart->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // Validate stock availability
        if (!$cart->variant->hasStock($data['quantity'])) {
            return response()->json([
                'message' => 'Not enough stock available',
                'available_stock' => $cart->variant->stock,
                'requested_quantity' => $data['quantity']
            ], 400);
        }

        $cart->update($data);

        // Load the relationships for the response (include product images for fallback)
        $cart->load('variant.product', 'variant.product.images', 'variant.color', 'variant.size', 'variant.images');

        // If the variant has no images, use product images
        if ($cart->variant && $cart->variant->images && $cart->variant->images->isEmpty()) {
            $variant = $cart->variant;
            $product = $variant->product;
            
            // For default variants (null color_id), use all product images without variant_id
            if ($product && !$variant->color_id) {
                $productImages = collect($product->images)->filter(function ($img) {
                    return !$img->variant_id;
                })->values();
                if ($productImages->isNotEmpty()) {
                    $variant->setRelation('images', $productImages);
                }
            } elseif ($product && $variant->color_id) {
                // For variants with color, use color-matched images
                $variantIdsSameColor = ProductVariant::where('product_id', $product->id)
                    ->where('color_id', $variant->color_id)
                    ->pluck('id');
                $colorImages = collect($product->images)->filter(function ($img) use ($variantIdsSameColor) {
                    return $img->variant_id && $variantIdsSameColor->contains($img->variant_id);
                })->values();
                if ($colorImages->isNotEmpty()) {
                    $variant->setRelation('images', $colorImages);
                }
            }
        }

        return response()->json($cart);
    }

    // Remove an item from the cart
    public function destroy(Cart $cart)
    {
        if ($cart->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cart->delete();

        return response()->json(['message' => 'Item removed']);
    }
}
