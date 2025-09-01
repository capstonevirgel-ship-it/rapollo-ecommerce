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
        $items = Cart::with('variant.product', 'variant.color', 'variant.size')
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

        if ($variant->stock < $data['quantity']) {
            return response()->json(['message' => 'Not enough stock'], 400);
        }

        $cartItem = Cart::updateOrCreate(
            [
                'user_id'   => Auth::id(),
                'variant_id'=> $data['variant_id'],
            ],
            [
                'quantity'  => \DB::raw("quantity + {$data['quantity']}"),
            ]
        );

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

        if ($cart->variant->stock < $data['quantity']) {
            return response()->json(['message' => 'Not enough stock'], 400);
        }

        $cart->update($data);

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
