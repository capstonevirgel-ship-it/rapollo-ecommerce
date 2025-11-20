<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TaxPrice;
use Illuminate\Support\Facades\Log;

class TaxPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $taxPrices = TaxPrice::orderBy('name')->get();
            
            return response()->json([
                'data' => $taxPrices
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch tax prices', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch tax prices'
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
                'name' => 'required|string|unique:tax_prices,name',
                'rate' => 'required|numeric|min:0|max:100',
                'description' => 'nullable|string|max:500',
                'is_active' => 'boolean'
            ]);

            $taxPrice = TaxPrice::create($validated);

            return response()->json([
                'message' => 'Tax price created successfully',
                'data' => $taxPrice
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create tax price', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Failed to create tax price'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $taxPrice = TaxPrice::findOrFail($id);
            
            return response()->json([
                'data' => $taxPrice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Tax price not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $taxPrice = TaxPrice::findOrFail($id);
            
            $validated = $request->validate([
                'name' => 'sometimes|required|string|unique:tax_prices,name,' . $id,
                'rate' => 'sometimes|required|numeric|min:0|max:100',
                'description' => 'nullable|string|max:500',
                'is_active' => 'sometimes|boolean'
            ]);

            $taxPrice->update($validated);

            return response()->json([
                'message' => 'Tax price updated successfully',
                'data' => $taxPrice
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update tax price', [
                'error' => $e->getMessage(),
                'id' => $id,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Failed to update tax price'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $taxPrice = TaxPrice::findOrFail($id);
            $taxPrice->delete();

            return response()->json([
                'message' => 'Tax price deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete tax price', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'error' => 'Failed to delete tax price'
            ], 500);
        }
    }

    /**
     * Get active tax rate for public use (e.g., checkout)
     */
    public function getActiveRate()
    {
        try {
            $totalRate = TaxPrice::getTotalTaxRate();
            
            return response()->json([
                'data' => [
                    'total_rate' => $totalRate,
                    'taxes' => TaxPrice::getAllActivePrices()->map(function($tax) {
                        return [
                            'name' => $tax->name,
                            'rate' => $tax->rate
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch active tax rate', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch tax rate'
            ], 500);
        }
    }
}

