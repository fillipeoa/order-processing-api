<?php

namespace Tests\Feature\Product;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_seller_can_upload_image(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $product = Product::factory()->for($seller)->create();

        $response = $this->actingAs($seller)
            ->postJson("/api/products/{$product->id}/image", [
                'image' => UploadedFile::fake()->image('photo.jpg', 800, 600),
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Product image uploaded successfully.');

        $product->refresh();
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_upload_replaces_old_image(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $product = Product::factory()->for($seller)->create();

        // Upload first image
        $firstImage = UploadedFile::fake()->image('first.jpg');
        $this->actingAs($seller)
            ->postJson("/api/products/{$product->id}/image", ['image' => $firstImage]);

        $product->refresh();
        $oldPath = $product->image_path;

        // Upload second image
        $secondImage = UploadedFile::fake()->image('second.jpg');
        $this->actingAs($seller)
            ->postJson("/api/products/{$product->id}/image", ['image' => $secondImage]);

        $product->refresh();
        $this->assertNotEquals($oldPath, $product->image_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_upload_rejects_invalid_type(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $product = Product::factory()->for($seller)->create();

        $response = $this->actingAs($seller)
            ->postJson("/api/products/{$product->id}/image", [
                'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_rejects_oversized_file(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $product = Product::factory()->for($seller)->create();

        $response = $this->actingAs($seller)
            ->postJson("/api/products/{$product->id}/image", [
                'image' => UploadedFile::fake()->image('large.jpg')->size(3000),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_buyer_cannot_upload_image(): void
    {
        $buyer = User::factory()->create(['role' => UserRole::Buyer]);
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $product = Product::factory()->for($seller)->create();

        $response = $this->actingAs($buyer)
            ->postJson("/api/products/{$product->id}/image", [
                'image' => UploadedFile::fake()->image('photo.jpg'),
            ]);

        $response->assertStatus(403);
    }

    public function test_non_owner_cannot_upload_image(): void
    {
        $seller1 = User::factory()->create(['role' => UserRole::Seller]);
        $seller2 = User::factory()->create(['role' => UserRole::Seller]);
        $product = Product::factory()->for($seller1)->create();

        $response = $this->actingAs($seller2)
            ->postJson("/api/products/{$product->id}/image", [
                'image' => UploadedFile::fake()->image('photo.jpg'),
            ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_upload_image(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);
        $product = Product::factory()->for($seller)->create();

        $response = $this->postJson("/api/products/{$product->id}/image", [
            'image' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertStatus(401);
    }
}
