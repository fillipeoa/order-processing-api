<?php

namespace App\Services;

use App\Filters\ProductFilter;
use App\Models\Product;
use App\Repositories\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\CursorPaginator;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}

    /**
     * @return Collection<int, Product>
     */
    public function listAll(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Search products with filters and cursor-based pagination.
     *
     * @return CursorPaginator<int, Product>
     */
    public function search(ProductFilter $filter): CursorPaginator
    {
        return $this->repository->search($filter);
    }

    public function findById(int $id): ?Product
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $categoryIds
     */
    public function create(array $data, int $userId, array $categoryIds = []): Product
    {
        $data['user_id'] = $userId;

        $product = $this->repository->create($data);

        if (! empty($categoryIds)) {
            $this->repository->syncCategories($product, $categoryIds);
            $product->load('categories');
        }

        return $product;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $categoryIds
     */
    public function update(Product $product, array $data, array $categoryIds = []): Product
    {
        $product = $this->repository->update($product, $data);

        if (! empty($categoryIds)) {
            $this->repository->syncCategories($product, $categoryIds);
            $product->load('categories');
        }

        return $product;
    }

    public function delete(Product $product): void
    {
        $this->repository->delete($product);
    }
}
