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
        $items = Cart::with('variant.product', 'variant.color', 'variant.size', 'variant.images')
            ->where('user_id', Auth::id())
            ->get();

        return response()->json($items);
    }

    // Add item to cart (or increase quantity if already exists)
    public function store(Request $request)
    {
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

        // Load the relationships for the response
        $cartItem->load('variant.product', 'variant.color', 'variant.size', 'variant.images');

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

        // Load the relationships for the response
        $cart->load('variant.product', 'variant.color', 'variant.size', 'variant.images');

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
