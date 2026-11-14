<?php

namespace Tests\Feature;

use Tests\TestCase;

class MetricsTest extends TestCase
{
    public function test_metrics_endpoint_returns_prometheus_metrics(): void
    {
        // Panggil endpoint aplikasi untuk memastikan dicatat oleh middleware CatatMetrik
        $this->get('/health');

        // Panggil endpoint /metrics
        $response = $this->get('/metrics');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('http_requests_total', $response->getContent());
    }
}
