<?php

namespace App\Filters;

class ProductFilter
{
    public function __construct(
        public readonly ?string $q = null,
        public readonly ?string $category = null,
        public readonly ?float $minPrice = null,
        public readonly ?float $maxPrice = null,
        public readonly ?string $status = null,
        public readonly ?int $sellerId = null,
        public readonly string $sort = 'newest',
        public readonly ?string $cursor = null,
    ) {}

    /**
     * Build a ProductFilter from a request's query params.
     *
     * @param  array<string, mixed>  $params
     */
    public static function fromArray(array $params): self
    {
        return new self(
            q: isset($params['q']) ? (string) $params['q'] : null,
            category: isset($params['category']) ? (string) $params['category'] : null,
            minPrice: isset($params['min_price']) ? (float) $params['min_price'] : null,
            maxPrice: isset($params['max_price']) ? (float) $params['max_price'] : null,
            status: isset($params['status']) ? (string) $params['status'] : null,
            sellerId: isset($params['seller_id']) ? (int) $params['seller_id'] : null,
            sort: isset($params['sort']) ? (string) $params['sort'] : 'newest',
            cursor: isset($params['cursor']) ? (string) $params['cursor'] : null,
        );
    }
}
