<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => CategoryResource::collection(Category::all())]);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return response()->json(['data' => new CategoryResource($category)], 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json(['data' => new CategoryResource($category)]);
    }

    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return response()->json(['data' => new CategoryResource($category)]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json(['message' => 'Kategori masih memiliki produk dan tidak dapat dihapus'], 409);
        }

        $category->delete();

        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
