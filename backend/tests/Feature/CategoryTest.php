<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_tolak_tanpa_token(): void
    {
        $this->getJson('/api/categories')->assertStatus(401);
    }

    public function test_list_kategori(): void
    {
        Sanctum::actingAs($this->user);
        Category::factory()->count(3)->create();

        $this->getJson('/api/categories')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_buat_kategori(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/categories', ['name' => 'Minuman'])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Minuman');

        $this->assertDatabaseHas('categories', ['name' => 'Minuman']);
    }

    public function test_buat_kategori_nama_duplikat(): void
    {
        Sanctum::actingAs($this->user);
        Category::factory()->create(['name' => 'Minuman']);

        $this->postJson('/api/categories', ['name' => 'Minuman'])
            ->assertStatus(422);
    }

    public function test_buat_kategori_tanpa_nama(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/categories', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_detail_kategori(): void
    {
        Sanctum::actingAs($this->user);
        $category = Category::factory()->create();

        $this->getJson("/api/categories/{$category->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $category->id);
    }

    public function test_detail_kategori_tidak_ada(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/categories/9999')
            ->assertStatus(404);
    }

    public function test_ubah_kategori(): void
    {
        Sanctum::actingAs($this->user);
        $category = Category::factory()->create();

        $this->putJson("/api/categories/{$category->id}", ['name' => 'Diperbarui'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Diperbarui');
    }

    public function test_hapus_kategori(): void
    {
        Sanctum::actingAs($this->user);
        $category = Category::factory()->create();

        $this->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Data berhasil dihapus']);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_hapus_kategori_yang_punya_produk(): void
    {
        Sanctum::actingAs($this->user);
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $this->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(409);
    }
}
