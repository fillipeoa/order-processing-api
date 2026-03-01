<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Remember a value in cache.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function remember(string $key, int $ttlSeconds, Closure $callback): mixed
    {
        return Cache::remember($key, $ttlSeconds, $callback);
    }

    /**
     * Forget a cached value.
     */
    public function forget(string $key): void
    {
        Cache::forget($key);
    }
}
