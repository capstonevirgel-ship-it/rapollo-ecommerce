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
    public function show(Category $category)
    {
        return response()->json($category->load('subcategories'));
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
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}

