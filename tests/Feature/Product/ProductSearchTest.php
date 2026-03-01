<?php

namespace Tests\Feature\Product;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seller = User::factory()->create(['role' => UserRole::Seller]);
    }

    public function test_can_list_all_products_without_filters(): void
    {
        Product::factory()->count(5)->for($this->seller)->create();

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure(['data', 'meta' => ['next_cursor', 'prev_cursor', 'per_page']]);
    }

    public function test_can_search_by_name(): void
    {
        Product::factory()->for($this->seller)->create(['name' => 'Awesome Notebook']);
        Product::factory()->for($this->seller)->create(['name' => 'Gaming Mouse']);

        $response = $this->getJson('/api/products?q=notebook');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Awesome Notebook');
    }

    public function test_can_search_by_description(): void
    {
        Product::factory()->for($this->seller)->create([
            'name' => 'Product A',
            'description' => 'Powered by a great processor',
        ]);
        Product::factory()->for($this->seller)->create([
            'name' => 'Product B',
            'description' => 'Simple product',
        ]);

        $response = $this->getJson('/api/products?q=processor');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_category(): void
    {
        $electronics = Category::factory()->create(['slug' => 'electronics']);
        $books = Category::factory()->create(['slug' => 'books']);

        $laptop = Product::factory()->for($this->seller)->create(['name' => 'Laptop']);
        $laptop->categories()->attach($electronics);

        $book = Product::factory()->for($this->seller)->create(['name' => 'PHP Book']);
        $book->categories()->attach($books);

        $response = $this->getJson('/api/products?category=electronics');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Laptop');
    }

    public function test_can_filter_by_min_price(): void
    {
        Product::factory()->for($this->seller)->create(['name' => 'Cheap', 'price' => 50.00]);
        Product::factory()->for($this->seller)->create(['name' => 'Expensive', 'price' => 500.00]);

        $response = $this->getJson('/api/products?min_price=100');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Expensive');
    }

    public function test_can_filter_by_max_price(): void
    {
        Product::factory()->for($this->seller)->create(['name' => 'Cheap', 'price' => 50.00]);
        Product::factory()->for($this->seller)->create(['name' => 'Expensive', 'price' => 500.00]);

        $response = $this->getJson('/api/products?max_price=100');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Cheap');
    }

    public function test_can_filter_by_price_range(): void
    {
        Product::factory()->for($this->seller)->create(['price' => 50.00]);
        Product::factory()->for($this->seller)->create(['price' => 250.00]);
        Product::factory()->for($this->seller)->create(['price' => 1000.00]);

        $response = $this->getJson('/api/products?min_price=100&max_price=500');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_status(): void
    {
        Product::factory()->for($this->seller)->create(['status' => 'active']);
        Product::factory()->for($this->seller)->create(['status' => 'inactive']);

        $response = $this->getJson('/api/products?status=active');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_seller(): void
    {
        $seller2 = User::factory()->create(['role' => UserRole::Seller]);

        Product::factory()->count(2)->for($this->seller)->create();
        Product::factory()->count(3)->for($seller2)->create();

        $response = $this->getJson("/api/products?seller_id={$this->seller->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_sort_by_price_asc(): void
    {
        Product::factory()->for($this->seller)->create(['name' => 'C', 'price' => 300]);
        Product::factory()->for($this->seller)->create(['name' => 'A', 'price' => 100]);
        Product::factory()->for($this->seller)->create(['name' => 'B', 'price' => 200]);

        $response = $this->getJson('/api/products?sort=price_asc');

        $response->assertOk();
        $prices = collect($response->json('data'))->pluck('price')->map(fn ($p) => (float) $p)->values()->all();
        $this->assertEquals([100.0, 200.0, 300.0], $prices);
    }

    public function test_can_sort_by_price_desc(): void
    {
        Product::factory()->for($this->seller)->create(['name' => 'A', 'price' => 100]);
        Product::factory()->for($this->seller)->create(['name' => 'B', 'price' => 200]);
        Product::factory()->for($this->seller)->create(['name' => 'C', 'price' => 300]);

        $response = $this->getJson('/api/products?sort=price_desc');

        $response->assertOk();
        $prices = collect($response->json('data'))->pluck('price')->map(fn ($p) => (float) $p)->values()->all();
        $this->assertEquals([300.0, 200.0, 100.0], $prices);
    }

    public function test_response_includes_cursor_pagination_meta(): void
    {
        Product::factory()->count(3)->for($this->seller)->create();

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonStructure([
                'meta' => ['next_cursor', 'prev_cursor', 'per_page'],
            ]);
    }

    public function test_combined_filters_work_together(): void
    {
        $electronics = Category::factory()->create(['slug' => 'electronics']);

        $match = Product::factory()->for($this->seller)->create([
            'name' => 'Laptop Pro',
            'price' => 2500.00,
            'status' => 'active',
        ]);
        $match->categories()->attach($electronics);

        // Should not match - wrong category
        Product::factory()->for($this->seller)->create(['price' => 2500.00, 'status' => 'active']);

        // Should not match - wrong price
        $cheap = Product::factory()->for($this->seller)->create([
            'name' => 'Laptop Cheap',
            'price' => 100.00,
            'status' => 'active',
        ]);
        $cheap->categories()->attach($electronics);

        $response = $this->getJson('/api/products?q=laptop&category=electronics&min_price=1000');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Laptop Pro');
    }
}
