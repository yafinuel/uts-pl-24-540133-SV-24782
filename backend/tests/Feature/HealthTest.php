<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_berhasil_saat_db_dan_cache_normal(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'checks' => [
                    'database' => [
                        'status' => 'healthy',
                    ],
                    'cache' => [
                        'status' => 'healthy',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'status',
                'timestamp',
                'checks' => [
                    'database' => ['status', 'message'],
                    'cache' => ['status', 'message'],
                ],
            ]);
    }

    public function test_api_health_endpoint_juga_tersedia(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('checks.database.status', 'healthy')
            ->assertJsonPath('checks.cache.status', 'healthy');
    }

    public function test_health_mengembalikan_503_saat_cache_bermasalah(): void
    {
        Cache::shouldReceive('put')->andThrow(new \Exception('Cache write error simulasi'));

        $response = $this->getJson('/health');

        $response->assertStatus(503)
            ->assertJsonPath('status', 'unhealthy')
            ->assertJsonPath('checks.cache.status', 'unhealthy');
    }

    public function test_health_mengembalikan_503_saat_database_bermasalah(): void
    {
        DB::shouldReceive('connection')->andThrow(new \Exception('Database connection failed simulasi'));

        $response = $this->getJson('/health');

        $response->assertStatus(503)
            ->assertJsonPath('status', 'unhealthy')
            ->assertJsonPath('checks.database.status', 'unhealthy');
    }
}
