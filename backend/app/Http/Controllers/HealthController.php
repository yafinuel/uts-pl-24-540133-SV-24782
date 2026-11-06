<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $checks = [];
        $isHealthy = true;

        // 1. Periksa koneksi basis data
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'healthy',
                'message' => 'Koneksi basis data berhasil',
            ];
        } catch (\Throwable $e) {
            $isHealthy = false;
            $checks['database'] = [
                'status' => 'unhealthy',
                'message' => 'Gagal terhubung ke basis data: ' . $e->getMessage(),
            ];

            Log::error('Pemeriksaan kesehatan basis data gagal', [
                'service' => 'backend',
                'action' => 'healthcheck',
                'target' => 'database',
                'ip' => $request->ip(),
                'reason' => $e->getMessage(),
            ]);
        }

        // 2. Periksa cache
        try {
            $testKey = 'health_check_' . hrtime(true);
            Cache::put($testKey, 'ok', 10);
            $cachedValue = Cache::get($testKey);
            Cache::forget($testKey);

            if ($cachedValue !== 'ok') {
                throw new \Exception('Nilai cache yang dibaca tidak sesuai.');
            }

            $checks['cache'] = [
                'status' => 'healthy',
                'message' => 'Cache berfungsi dengan baik',
            ];
        } catch (\Throwable $e) {
            $isHealthy = false;
            $checks['cache'] = [
                'status' => 'unhealthy',
                'message' => 'Gagal membaca/menulis cache: ' . $e->getMessage(),
            ];

            Log::error('Pemeriksaan kesehatan cache gagal', [
                'service' => 'backend',
                'action' => 'healthcheck',
                'target' => 'cache',
                'ip' => $request->ip(),
                'reason' => $e->getMessage(),
            ]);
        }

        $statusCode = $isHealthy ? 200 : 503;

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $statusCode);
    }
}
