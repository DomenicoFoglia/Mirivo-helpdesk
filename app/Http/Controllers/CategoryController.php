<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        $categories = Category::where('company_id', $user->company_id)->paginate(15);

        return $categories;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $category = Category::create([
                        'company_id' => $user->company_id,
                        'name' => $validated['name']
                    ]);

        return response()->json($category, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();

        $category = Category::where('company_id', $user->company_id)->findOrFail($id);

        return $category;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        $category = Category::where('company_id', $user->company_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|Max:255'
        ]);

        $category->update($validated);

        return $category;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        $category = Category::where('company_id', $user->company_id)->findOrFail($id);

        $category->delete();

        return response()->noContent();
    }
}
