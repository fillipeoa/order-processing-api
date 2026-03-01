<?php

namespace Tests\Feature\Cache;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_are_cached(): void
    {
        Category::factory()->count(3)->create();

        // First call — populates cache
        $this->getJson('/api/categories')->assertOk();

        $this->assertTrue(Cache::has('categories:all'));
    }

    public function test_category_cache_invalidated_on_create(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);

        // Populate cache
        $this->getJson('/api/categories');
        $this->assertTrue(Cache::has('categories:all'));

        // Create invalidates
        $this->actingAs($seller)->postJson('/api/categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
        ]);

        $this->assertFalse(Cache::has('categories:all'));
    }

    public function test_category_cache_invalidated_on_update(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $category = Category::factory()->create();

        // Populate cache
        $this->getJson('/api/categories');
        $this->assertTrue(Cache::has('categories:all'));

        // Update invalidates
        $this->actingAs($seller)->putJson("/api/categories/{$category->id}", [
            'name' => 'Updated',
        ]);

        $this->assertFalse(Cache::has('categories:all'));
    }

    public function test_category_cache_invalidated_on_delete(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $category = Category::factory()->create();

        // Populate cache
        $this->getJson('/api/categories');
        $this->assertTrue(Cache::has('categories:all'));

        // Delete invalidates
        $this->actingAs($seller)->deleteJson("/api/categories/{$category->id}");

        $this->assertFalse(Cache::has('categories:all'));
    }

    public function test_product_is_cached(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $product = Product::factory()->for($seller)->create();

        // First call — populates cache
        $this->getJson("/api/products/{$product->id}")->assertOk();

        $this->assertTrue(Cache::has("products:{$product->id}"));
    }

    public function test_product_cache_invalidated_on_update(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $product = Product::factory()->for($seller)->create();

        // Populate cache
        $this->getJson("/api/products/{$product->id}");
        $this->assertTrue(Cache::has("products:{$product->id}"));

        // Update invalidates
        $this->actingAs($seller)->putJson("/api/products/{$product->id}", [
            'name' => 'Updated Product',
        ]);

        $this->assertFalse(Cache::has("products:{$product->id}"));
    }

    public function test_product_cache_invalidated_on_delete(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $product = Product::factory()->for($seller)->create();

        // Populate cache
        $this->getJson("/api/products/{$product->id}");
        $this->assertTrue(Cache::has("products:{$product->id}"));

        // Delete invalidates
        $this->actingAs($seller)->deleteJson("/api/products/{$product->id}");

        $this->assertFalse(Cache::has("products:{$product->id}"));
    }
}
