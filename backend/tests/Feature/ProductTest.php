<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'category_id' => $this->category->id,
            'sku' => 'PRD-001',
            'name' => 'Produk Test',
            'price' => 10000,
            'stock' => 5,
        ], $overrides);
    }

    public function test_tolak_tanpa_token(): void
    {
        $this->getJson('/api/products')->assertStatus(401);
    }

    public function test_list_produk_dengan_paginasi(): void
    {
        Sanctum::actingAs($this->user);
        Product::factory()->count(3)->create(['category_id' => $this->category->id]);

        $this->getJson('/api/products')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_filter_category_id(): void
    {
        Sanctum::actingAs($this->user);
        $other = Category::factory()->create();
        Product::factory()->create(['category_id' => $this->category->id]);
        Product::factory()->create(['category_id' => $other->id]);

        $response = $this->getJson("/api/products?category_id={$this->category->id}");
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_filter_search(): void
    {
        Sanctum::actingAs($this->user);
        Product::factory()->create(['category_id' => $this->category->id, 'name' => 'Teh Botol', 'sku' => 'TH-001']);
        Product::factory()->create(['category_id' => $this->category->id, 'name' => 'Air Mineral', 'sku' => 'AR-001']);

        $response = $this->getJson('/api/products?search=Teh');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_buat_produk(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/products', $this->validData())
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Produk Test');
    }

    public function test_buat_produk_sku_duplikat(): void
    {
        Sanctum::actingAs($this->user);
        Product::factory()->create(['category_id' => $this->category->id, 'sku' => 'PRD-001']);

        $this->postJson('/api/products', $this->validData())
            ->assertStatus(422);
    }

    public function test_buat_produk_category_tidak_ada(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/products', $this->validData(['category_id' => 9999]))
            ->assertStatus(422);
    }

    public function test_buat_produk_field_wajib(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/products', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['category_id', 'sku', 'name', 'price', 'stock']]);
    }

    public function test_detail_produk(): void
    {
        Sanctum::actingAs($this->user);
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $this->getJson("/api/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_detail_produk_tidak_ada(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/products/9999')->assertStatus(404);
    }

    public function test_ubah_produk(): void
    {
        Sanctum::actingAs($this->user);
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $this->putJson("/api/products/{$product->id}", $this->validData(['name' => 'Diperbarui']))
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Diperbarui');
    }

    public function test_hapus_produk(): void
    {
        Sanctum::actingAs($this->user);
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $this->deleteJson("/api/products/{$product->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Data berhasil dihapus']);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
