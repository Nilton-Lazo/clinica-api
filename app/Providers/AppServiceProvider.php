<?php

namespace App\Providers;

use App\Core\realtime\RealtimeModelObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Core\audit\AuditContext::class, function ($app) {
            return new \App\Core\audit\AuditContext($app->has('request') ? $app['request'] : null);
        });
    
        $this->app->singleton(\App\Core\audit\AuditService::class, function ($app) {
            return new \App\Core\audit\AuditService(
                $app->make(\App\Core\audit\AuditContext::class)
            );
        });

        $this->app->singleton(\App\Core\reporting\BrowsershotPdfRenderer::class);
        $this->app->singleton(\App\Core\reporting\PdfReportRenderer::class);

        $this->app->singleton(\App\Core\reporting\ReportExportResponse::class, function ($app) {
            return new \App\Core\reporting\ReportExportResponse(
                $app->make(\App\Core\reporting\PdfReportRenderer::class)
            );
        });

        $this->app->singleton(\App\Core\clinica\services\ClinicaConfigService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (array_keys(config('realtime.models', [])) as $modelClass) {
            if (class_exists($modelClass)) {
                $modelClass::observe(RealtimeModelObserver::class);
            }
        }
    }
}
