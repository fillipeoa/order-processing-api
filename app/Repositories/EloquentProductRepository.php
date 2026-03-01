<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

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
}
