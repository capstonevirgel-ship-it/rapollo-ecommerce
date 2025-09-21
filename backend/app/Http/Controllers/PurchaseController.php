<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            // Calculate total
            $total = collect($validated['items'])->sum(function ($item) {
                return $item['quantity'] * $item['price'];
            });

            // Create purchase
            $purchase = Purchase::create([
                'user_id' => Auth::id(),
                'total' => $total,
                'status' => 'pending',
            ]);

            // Create purchase items
            foreach ($validated['items'] as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            // Note: Cart will be cleared after successful payment, not here

            DB::commit();

            return response()->json([
                'message' => 'Purchase created successfully',
                'data' => $purchase->load(['items.variant.product', 'payment']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'error' => 'Failed to create purchase',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function index()
    {
        $purchases = Purchase::where('user_id', Auth::id())
            ->with(['items.variant.product', 'payment'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $purchases,
        ]);
    }

    public function show($id)
    {
        $purchase = Purchase::where('user_id', Auth::id())
            ->with(['items.variant.product', 'payment'])
            ->findOrFail($id);

        return response()->json([
            'data' => $purchase,
        ]);
    }
}