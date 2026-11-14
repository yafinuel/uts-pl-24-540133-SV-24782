<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\Prometheus\CollectorRegistry::class, function () {
            $adapter = new \App\Metrics\FileAdapter();
            return new \Prometheus\CollectorRegistry($adapter);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
