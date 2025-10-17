<?php

namespace App\Http\Controllers;

use App\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    public function index()
    {
        $sizes = Size::orderBy('sort_order')->orderBy('name')->get();
        return response()->json($sizes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:sizes,name',
            'description' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0'
        ]);

        $size = Size::create($validated);
        return response()->json($size, 201);
    }

    public function update(Request $request, Size $size)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:sizes,name,' . $size->id,
            'description' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0'
        ]);

        $size->update($validated);
        return response()->json($size);
    }

    public function destroy(Size $size)
    {
        $size->delete();
        return response()->json(['message' => 'Size deleted successfully']);
    }
}
