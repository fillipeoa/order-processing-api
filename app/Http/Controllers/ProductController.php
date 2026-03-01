<?php

namespace App\Http\Controllers;

use App\Filters\ProductFilter;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UploadProductImageRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\CacheService;
use App\Services\ProductService;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    private const CACHE_TTL = 1800; // 30 minutes

    public function __construct(
        private readonly ProductService $productService,
        private readonly StorageService $storageService,
        private readonly CacheService $cacheService,
    ) {}

    /**
     * Display a listing of the resource with optional search/filter.
     */
    public function index(Request $request): JsonResponse
    {
        $filter = ProductFilter::fromArray($request->query());
        $products = $this->productService->search($filter);

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'next_cursor' => $products->nextCursor()?->encode(),
                'prev_cursor' => $products->previousCursor()?->encode(),
                'per_page' => $products->perPage(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        Gate::authorize('create', Product::class);

        $validated = $request->validated();
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $product = $this->productService->create($validated, $user->id, $categoryIds);
        $product->load(['user', 'categories']);

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => new ProductResource($product),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): JsonResponse
    {
        /** @var Product $cached */
        $cached = $this->cacheService->remember(
            "products:{$product->id}",
            self::CACHE_TTL,
            function () use ($product) {
                $product->load(['user', 'categories']);

                return $product;
            }
        );

        return response()->json([
            'data' => new ProductResource($cached),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        $validated = $request->validated();
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        $product = $this->productService->update($product, $validated, $categoryIds);
        $product->load(['user', 'categories']);

        $this->cacheService->forget("products:{$product->id}");

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        Gate::authorize('delete', $product);

        $productId = $product->id;
        $this->productService->delete($product);

        $this->cacheService->forget("products:{$productId}");

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    /**
     * Upload an image for the product.
     */
    public function uploadImage(UploadProductImageRequest $request, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        // Delete old image if exists
        if ($product->image_path) {
            $this->storageService->delete($product->image_path);
        }

        $path = $this->storageService->upload($request->file('image'), 'products');

        $product->update(['image_path' => $path]);
        $product->load(['user', 'categories']);

        $this->cacheService->forget("products:{$product->id}");

        return response()->json([
            'message' => 'Product image uploaded successfully.',
            'data' => new ProductResource($product),
        ]);
    }
}
