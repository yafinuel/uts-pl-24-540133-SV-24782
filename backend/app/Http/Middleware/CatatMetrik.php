<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpFoundation\Response;

class CatatMetrik
{
    public function __construct(protected CollectorRegistry $registry)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Jangan catat tarikan metrik Prometheus sendiri
        if ($request->is('metrics')) {
            return $response;
        }

        try {
            $method = $request->method();
            $status = (string) $response->getStatusCode();

            // Sesuai materi Pertemuan 11 Slide 14 & 15: http_requests_total
            $counter = $this->registry->getOrRegisterCounter(
                '',
                'http_requests_total',
                'Jumlah permintaan HTTP yang diproses',
                ['method', 'status']
            );
            $counter->inc([$method, $status]);

            // Sesuai materi Slide 11: app_http_requests_total
            $appCounter = $this->registry->getOrRegisterCounter(
                'app',
                'http_requests_total',
                'Jumlah permintaan HTTP aplikasi',
                ['method', 'status']
            );
            $appCounter->inc([$method, $status]);
        } catch (\Throwable) {
            // Kegagalan pencatatan metrik tidak boleh mengganggu respons klien
        }

        return $response;
    }
}
