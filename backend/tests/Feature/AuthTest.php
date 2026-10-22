<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_sukses(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data' => ['token', 'user']]);
    }

    public function test_login_kredensial_salah(): void
    {
        User::factory()->create(['email' => 'test@example.com', 'password' => bcrypt('benar')]);

        $this->postJson('/api/login', ['email' => 'test@example.com', 'password' => 'salah'])
            ->assertStatus(401)
            ->assertJson(['message' => 'Email atau password salah']);
    }

    public function test_login_tanpa_field(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422);
    }

    public function test_logout_menghapus_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/logout')->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
