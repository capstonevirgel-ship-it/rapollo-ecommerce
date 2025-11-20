<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShippingPrice;
use Illuminate\Support\Facades\Log;

class ShippingPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $shippingPrices = ShippingPrice::orderBy('region')->get();
            $availableRegions = ShippingPrice::getAvailableRegions();
            
            return response()->json([
                'data' => $shippingPrices,
                'available_regions' => $availableRegions
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch shipping prices', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch shipping prices'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'region' => 'required|string|unique:shipping_prices,region',
                'price' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:500',
                'is_active' => 'boolean'
            ]);

            $shippingPrice = ShippingPrice::create($validated);

            return response()->json([
                'message' => 'Shipping price created successfully',
                'data' => $shippingPrice
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create shipping price', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Failed to create shipping price'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $shippingPrice = ShippingPrice::findOrFail($id);
            
            return response()->json([
                'data' => $shippingPrice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Shipping price not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $shippingPrice = ShippingPrice::findOrFail($id);
            
            $validated = $request->validate([
                'region' => 'sometimes|required|string|unique:shipping_prices,region,' . $id,
                'price' => 'sometimes|required|numeric|min:0',
                'description' => 'nullable|string|max:500',
                'is_active' => 'sometimes|boolean'
            ]);

            $shippingPrice->update($validated);

            return response()->json([
                'message' => 'Shipping price updated successfully',
                'data' => $shippingPrice
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update shipping price', [
                'error' => $e->getMessage(),
                'id' => $id,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Failed to update shipping price'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $shippingPrice = ShippingPrice::findOrFail($id);
            $shippingPrice->delete();

            return response()->json([
                'message' => 'Shipping price deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete shipping price', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'error' => 'Failed to delete shipping price'
            ], 500);
        }
    }

    /**
     * Get shipping prices for public use (e.g., checkout)
     */
    public function getActivePrices()
    {
        try {
            $prices = ShippingPrice::getAllActivePrices();
            
            return response()->json([
                'data' => $prices
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch active shipping prices', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch shipping prices'
            ], 500);
        }
    }

    /**
     * Bulk update shipping prices
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $validated = $request->validate([
                'prices' => 'required|array',
                'prices.*.id' => 'required|exists:shipping_prices,id',
                'prices.*.price' => 'required|numeric|min:0',
                'prices.*.is_active' => 'boolean'
            ]);

            foreach ($validated['prices'] as $priceData) {
                $shippingPrice = ShippingPrice::find($priceData['id']);
                if ($shippingPrice) {
                    $shippingPrice->update([
                        'price' => $priceData['price'],
                        'is_active' => $priceData['is_active'] ?? $shippingPrice->is_active
                    ]);
                }
            }

            return response()->json([
                'message' => 'Shipping prices updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to bulk update shipping prices', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Failed to update shipping prices'
            ], 500);
        }
    }
}
