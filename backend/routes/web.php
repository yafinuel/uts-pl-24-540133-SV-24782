<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

Route::get('/health', [HealthController::class, 'check']);

Route::get('/metrics', function (CollectorRegistry $reg) {
    return response(
        (new RenderTextFormat)->render($reg->getMetricFamilySamples()),
        200,
        ['Content-Type' => RenderTextFormat::MIME_TYPE]
    );
});

Route::get('/', function () {
    return view('welcome');
});
