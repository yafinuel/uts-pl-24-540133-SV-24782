<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->is('health');
        });

        // 1. Log terstruktur saat validasi input gagal (Level: warning)
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            Log::warning('Validasi permintaan gagal', [
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'action' => 'validate_request',
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'reason' => 'Data input tidak memenuhi kriteria validasi',
                'invalid_fields' => array_keys($e->errors()),
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], $e->status);
        });

        // 2. Log terstruktur saat query basis data gagal (Level: error)
        $exceptions->render(function (\Illuminate\Database\QueryException $e, Request $request) {
            Log::error('Operasi query basis data gagal', [
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'action' => 'database_query',
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'reason' => $e->getMessage(),
            ]);

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Terjadi kesalahan pada layanan basis data.',
                ], 500);
            }
        });

        // 3. Log terstruktur saat koneksi basis data gagal (Level: error)
        $exceptions->render(function (\PDOException $e, Request $request) {
            Log::error('Koneksi basis data terputus atau gagal dihubungi', [
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'action' => 'database_connection',
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'reason' => $e->getMessage(),
            ]);

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Layanan basis data tidak dapat dihubungi.',
                ], 503);
            }
        });
    })->create();
