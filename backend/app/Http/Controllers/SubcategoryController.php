<?php

namespace App\Http\Controllers;

use App\Models\Subcategory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubcategoryController extends Controller
{
    /**
     * Display a listing of subcategories.
     */
    public function index()
    {
        $subcategories = Subcategory::with('category')->get();
        return response()->json($subcategories);
    }

    /**
     * Store a newly created subcategory.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:50|unique:subcategories,name',
            'meta_title' => 'nullable|string|max:150',
            'meta_description' => 'nullable|string|max:255',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $subcategory = Subcategory::create($validated);

        return response()->json([
            'message' => 'Subcategory created successfully',
            'data' => $subcategory->load('category')
        ], 201);
    }

    /**
     * Display the specified subcategory.
     */
    public function show($subcategory)
    {
        // Check if the parameter is numeric (ID) or string (slug)
        if (is_numeric($subcategory)) {
            // Handle by ID
            $subcategory = Subcategory::with([
                'category',
                'products' => function($query) {
                    $query->where('is_active', true);
                },
                'products.brand',
                'products.variants.color',
                'products.variants.size',
                'products.variants.images',
                'products.images'
            ])->findOrFail($subcategory);
        } else {
            // Handle by slug
            $subcategory = Subcategory::with([
                'category',
                'products' => function($query) {
                    $query->where('is_active', true);
                },
                'products.brand',
                'products.variants.color',
                'products.variants.size',
                'products.variants.images',
                'products.images'
            ])->where('slug', $subcategory)->firstOrFail();
        }
        
        return response()->json($subcategory);
    }

    /**
     * Display the specified subcategory by ID.
     */
    public function showById($id)
    {
        $subcategory = Subcategory::with([
            'category',
            'products' => function($query) {
                $query->where('is_active', true);
            },
            'products.brand',
            'products.variants.color',
            'products.variants.size',
            'products.variants.images',
            'products.images'
        ])->findOrFail($id);
        return response()->json($subcategory);
    }

    /**
     * Update the specified subcategory.
     */
    public function update(Request $request, Subcategory $subcategory)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:50|unique:subcategories,name,' . $subcategory->id,
            'meta_title' => 'nullable|string|max:150',
            'meta_description' => 'nullable|string|max:255',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $subcategory->update($validated);

        return response()->json([
            'message' => 'Subcategory updated successfully',
            'data' => $subcategory->load('category')
        ]);
    }

    /**
     * Remove the specified subcategory.
     */
    public function destroy(Subcategory $subcategory)
    {
        // Check if subcategory has products
        if ($subcategory->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete subcategory: It contains products. Please delete or reassign products first.',
                'error' => 'DEPENDENCY_EXISTS'
            ], 422);
        }
        
        $subcategory->delete();

        return response()->json([
            'message' => 'Subcategory deleted successfully'
        ]);
    }
}
