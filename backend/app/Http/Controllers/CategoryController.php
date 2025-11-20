<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index()
    {
        $categories = Category::with('subcategories')->get();
        return response()->json($categories);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:categories,name',
            'meta_title' => 'nullable|string|max:150',
            'meta_description' => 'nullable|string|max:255',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show($category)
    {
        // Check if the parameter is numeric (ID) or string (slug)
        if (is_numeric($category)) {
            // Handle by ID
            $category = Category::with('subcategories')->findOrFail($category);
        } else {
            // Handle by slug
            $category = Category::with('subcategories')->where('slug', $category)->firstOrFail();
        }
        
        return response()->json($category);
    }

    /**
     * Display the specified category by ID.
     */
    public function showById($id)
    {
        $category = Category::with('subcategories')->findOrFail($id);
        return response()->json($category);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:categories,name,' . $category->id,
            'meta_title' => 'nullable|string|max:150',
            'meta_description' => 'nullable|string|max:255',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category)
    {
        // Check if any subcategories have products
        $hasProducts = $category->subcategories()
            ->whereHas('products')
            ->exists();
        
        if ($hasProducts) {
            return response()->json([
                'message' => 'Cannot delete category: It contains subcategories with products. Please delete or reassign products first.',
                'error' => 'DEPENDENCY_EXISTS'
            ], 422);
        }
        
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}

