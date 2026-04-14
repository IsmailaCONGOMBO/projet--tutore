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
        $this->app->bind(
            \App\Services\Plagiat\Contracts\TextExtractionServiceInterface::class,
            \App\Services\Plagiat\TextExtractionService::class
        );

        $this->app->bind(
            \App\Services\Plagiat\Contracts\PreprocessingServiceInterface::class,
            \App\Services\Plagiat\PreprocessingService::class
        );

        $this->app->bind(
            \App\Services\Plagiat\Contracts\DocumentSegmentationServiceInterface::class,
            \App\Services\Plagiat\DocumentSegmentationService::class
        );

        $this->app->bind(
            \App\Services\Plagiat\Contracts\TFIDFServiceInterface::class,
            \App\Services\Plagiat\TFIDFService::class
        );

        $this->app->bind(
            \App\Services\Plagiat\Contracts\SimilarityServiceInterface::class,
            \App\Services\Plagiat\SimilarityService::class
        );

        $this->app->bind(
            \App\Services\Plagiat\Contracts\ParaphraseDetectionServiceInterface::class,
            \App\Services\Plagiat\ParaphraseDetectionService::class
        );

        $this->app->bind(
            \App\Services\Plagiat\Contracts\PlagiatAnalyzerServiceInterface::class,
            \App\Services\Plagiat\PlagiatAnalyzerService::class
        );

        $this->app->bind(
            \App\Services\Plagiat\Contracts\PlagiatReportServiceInterface::class,
            \App\Services\Plagiat\PlagiatReportService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
