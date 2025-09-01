<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index()
    {
        // List all brands
        $brands = Brand::orderBy('name')->get();

        return response()->json($brands);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255|unique:brands,name',
            'logo'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $logoUrl = null;

        if ($request->hasFile('logo')) {
            $logoUrl = $request->file('logo')->store('brands', 'public');
        }

        $brand = Brand::create([
            'name'             => $validated['name'],
            'slug'             => Str::slug($validated['name']),
            'logo_url'         => $logoUrl,
            'meta_title'       => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ]);

        return response()->json($brand, 201);
    }

    public function show(Brand $brand)
    {
        return response()->json($brand);
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name'             => 'sometimes|string|max:255|unique:brands,name,' . $brand->id,
            'logo'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        if ($request->has('name')) {
            $brand->name = $validated['name'];
            $brand->slug = Str::slug($validated['name']);
        }

        if ($request->hasFile('logo')) {
            $brand->logo_url = $request->file('logo')->store('brands', 'public');
        }

        if ($request->has('meta_title')) {
            $brand->meta_title = $validated['meta_title'];
        }

        if ($request->has('meta_description')) {
            $brand->meta_description = $validated['meta_description'];
        }

        $brand->save();

        return response()->json($brand);
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();

        return response()->json([
            'message' => 'Brand deleted successfully',
        ]);
    }
}
