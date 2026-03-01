<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $categories = Category::all();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        Gate::authorize('create', Category::class);

        $category = Category::create($request->validated());

        return response()->json([
            'message' => 'Category created successfully.',
            'data' => new CategoryResource($category),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        Gate::authorize('update', Category::class);

        $category->update($request->validated());

        return response()->json([
            'message' => 'Category updated successfully.',
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        Gate::authorize('delete', Category::class);

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
