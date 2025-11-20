<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $items = Cart::with('variant.product', 'variant.product.images', 'variant.color', 'variant.size', 'variant.images')
            ->where('user_id', Auth::id())
            ->get();

        // Ensure variant images are populated: if a variant has no images, use product images for the same color
        foreach ($items as $item) {
            if ($item->variant && $item->variant->images && $item->variant->images->isEmpty()) {
                $variant = $item->variant;
                $product = $variant->product;
                if ($product && $variant->color_id) {
                    // Find variant ids of the same color for this product
                    $variantIdsSameColor = ProductVariant::where('product_id', $product->id)
                        ->where('color_id', $variant->color_id)
                        ->pluck('id');

                    // Filter product images that belong to variants of the same color
                    $colorImages = collect($product->images)->filter(function ($img) use ($variantIdsSameColor) {
                        return $img->variant_id && $variantIdsSameColor->contains($img->variant_id);
                    })->values();

                    if ($colorImages->isNotEmpty()) {
                        // Override the variant images relation with color-matched images
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
            'variant_id' => 'required|exists:product_variants,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $variant = ProductVariant::findOrFail($data['variant_id']);

        // Check if variant is out of stock
        if ($variant->isOutOfStock()) {
            return response()->json([
                'message' => 'This item is out of stock',
                'stock' => 0
            ], 400);
        }

        // Check if item already exists
        $existingItem = Cart::where('user_id', Auth::id())
            ->where('variant_id', $data['variant_id'])
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
                'variant_id'=> $data['variant_id'],
                'quantity'  => $data['quantity'],
            ]);
        }

        // Load the relationships for the response (include product images for color-based fallback)
        $cartItem->load('variant.product', 'variant.product.images', 'variant.color', 'variant.size', 'variant.images');

        // If the variant has no images, use product images for the same color
        if ($cartItem->variant && $cartItem->variant->images && $cartItem->variant->images->isEmpty()) {
            $variant = $cartItem->variant;
            $product = $variant->product;
            if ($product && $variant->color_id) {
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

        // If the variant has no images, use product images for the same color
        if ($cart->variant && $cart->variant->images && $cart->variant->images->isEmpty()) {
            $variant = $cart->variant;
            $product = $variant->product;
            if ($product && $variant->color_id) {
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
