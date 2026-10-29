<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->environment('production') || env('FORCE_HTTPS', false)) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->isLocal() && class_exists(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class)) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
        $this->app->singleton(\App\Services\NepaliDateService::class, function () {
            return new \App\Services\NepaliDateService();
        });
        $this->app->alias(\App\Services\NepaliDateService::class, \App\Services\BikramSambatService::class);
        $this->app->alias(\App\Services\NepaliDateService::class, 'nepali_date');

        $this->app->singleton(\App\Services\AuditLogService::class, function () {
            return new \App\Services\AuditLogService();
        });
        $this->app->alias(\App\Services\AuditLogService::class, 'audit_log');
    }
}
