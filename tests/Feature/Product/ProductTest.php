<?php

namespace Tests\Feature\Product;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_list_products(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        Product::factory()->count(3)->for($seller)->create();

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_anyone_can_view_product(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $product = Product::factory()->for($seller)->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', $product->name)
            ->assertJsonPath('data.user.id', $seller->id);
    }

    public function test_seller_can_create_product(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $category = Category::factory()->create();

        $response = $this->actingAs($seller)
            ->postJson('/api/products', [
                'name' => 'Notebook Pro',
                'description' => 'A powerful notebook.',
                'price' => 2999.99,
                'stock' => 10,
                'category_ids' => [$category->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Notebook Pro')
            ->assertJsonPath('data.price', '2999.99')
            ->assertJsonPath('data.user.id', $seller->id);

        $this->assertDatabaseHas('products', ['name' => 'Notebook Pro']);
        $this->assertDatabaseHas('category_product', [
            'product_id' => $response->json('data.id'),
            'category_id' => $category->id,
        ]);
    }

    public function test_buyer_cannot_create_product(): void
    {
        $buyer = User::factory()->create(['role' => UserRole::Buyer]);

        $response = $this->actingAs($buyer)
            ->postJson('/api/products', [
                'name' => 'Notebook',
                'description' => 'A notebook.',
                'price' => 999.99,
                'stock' => 5,
            ]);

        $response->assertStatus(403);
    }

    public function test_seller_can_update_own_product(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $product = Product::factory()->for($seller)->create();

        $response = $this->actingAs($seller)
            ->putJson("/api/products/{$product->id}", [
                'name' => 'Updated Product',
                'price' => 1500.00,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Product');
    }

    public function test_seller_cannot_update_others_product(): void
    {
        $seller1 = User::factory()->create(['role' => UserRole::Seller]);
        $seller2 = User::factory()->create(['role' => UserRole::Seller]);
        $product = Product::factory()->for($seller1)->create();

        $response = $this->actingAs($seller2)
            ->putJson("/api/products/{$product->id}", [
                'name' => 'Hacked Product',
            ]);

        $response->assertStatus(403);
    }

    public function test_seller_can_delete_own_product(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $product = Product::factory()->for($seller)->create();

        $response = $this->actingAs($seller)
            ->deleteJson("/api/products/{$product->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_seller_cannot_delete_others_product(): void
    {
        $seller1 = User::factory()->create(['role' => UserRole::Seller]);
        $seller2 = User::factory()->create(['role' => UserRole::Seller]);
        $product = Product::factory()->for($seller1)->create();

        $response = $this->actingAs($seller2)
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(403);
    }

    public function test_create_product_validation_fails(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);

        $response = $this->actingAs($seller)
            ->postJson('/api/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'price', 'stock']);
    }

    public function test_unauthenticated_cannot_create_product(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'Notebook',
            'description' => 'A notebook.',
            'price' => 999.99,
            'stock' => 5,
        ]);

        $response->assertStatus(401);
    }
}
