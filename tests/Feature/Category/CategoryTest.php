<?php

namespace Tests\Feature\Category;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_list_categories(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_anyone_can_view_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', $category->name)
            ->assertJsonPath('data.slug', $category->slug);
    }

    public function test_seller_can_create_category(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);

        $response = $this->actingAs($seller)
            ->postJson('/api/categories', [
                'name' => 'Electronics',
                'slug' => 'electronics',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Electronics')
            ->assertJsonPath('data.slug', 'electronics');

        $this->assertDatabaseHas('categories', ['name' => 'Electronics']);
    }

    public function test_buyer_cannot_create_category(): void
    {
        $buyer = User::factory()->create(['role' => UserRole::Buyer]);

        $response = $this->actingAs($buyer)
            ->postJson('/api/categories', [
                'name' => 'Electronics',
                'slug' => 'electronics',
            ]);

        $response->assertStatus(403);
    }

    public function test_seller_can_update_category(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $category = Category::factory()->create();

        $response = $this->actingAs($seller)
            ->putJson("/api/categories/{$category->id}", [
                'name' => 'Updated Name',
                'slug' => 'updated-name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_buyer_cannot_update_category(): void
    {
        $buyer = User::factory()->create(['role' => UserRole::Buyer]);
        $category = Category::factory()->create();

        $response = $this->actingAs($buyer)
            ->putJson("/api/categories/{$category->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_seller_can_delete_category(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $category = Category::factory()->create();

        $response = $this->actingAs($seller)
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_buyer_cannot_delete_category(): void
    {
        $buyer = User::factory()->create(['role' => UserRole::Buyer]);
        $category = Category::factory()->create();

        $response = $this->actingAs($buyer)
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(403);
    }

    public function test_create_category_validation_fails(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);

        $response = $this->actingAs($seller)
            ->postJson('/api/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug']);
    }

    public function test_create_category_fails_with_duplicate_name(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        Category::factory()->create(['name' => 'Electronics', 'slug' => 'electronics']);

        $response = $this->actingAs($seller)
            ->postJson('/api/categories', [
                'name' => 'Electronics',
                'slug' => 'electronics-2',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_unauthenticated_cannot_create_category(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $response->assertStatus(401);
    }
}
