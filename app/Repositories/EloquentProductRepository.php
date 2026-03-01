<?php

namespace App\Repositories;

use App\Filters\ProductFilter;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\CursorPaginator;

class EloquentProductRepository implements ProductRepositoryInterface
{
    /**
     * @return Collection<int, Product>
     */
    public function all(): Collection
    {
        return Product::with(['user', 'categories'])->get();
    }

    public function findById(int $id): ?Product
    {
        return Product::with(['user', 'categories'])->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->fresh() ?? $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * @param  list<int>  $categoryIds
     */
    public function syncCategories(Product $product, array $categoryIds): void
    {
        $product->categories()->sync($categoryIds);
    }

    /**
     * Search products with filters and cursor-based pagination.
     *
     * @return CursorPaginator<int, Product>
     */
    public function search(ProductFilter $filter): CursorPaginator
    {
        $query = Product::with(['user', 'categories']);

        if ($filter->q !== null && $filter->q !== '') {
            $search = '%'.$filter->q.'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', $search)
                    ->orWhere('description', 'LIKE', $search);
            });
        }

        if ($filter->category !== null && $filter->category !== '') {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $filter->category));
        }

        if ($filter->minPrice !== null) {
            $query->where('price', '>=', $filter->minPrice);
        }

        if ($filter->maxPrice !== null) {
            $query->where('price', '<=', $filter->maxPrice);
        }

        if ($filter->status !== null && $filter->status !== '') {
            $query->where('status', $filter->status);
        }

        if ($filter->sellerId !== null) {
            $query->where('user_id', $filter->sellerId);
        }

        $query = match ($filter->sort) {
            'price_asc' => $query->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        return $query->cursorPaginate(15);
    }
}
