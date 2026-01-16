<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ExportTransformation\OperationRegistry;
use App\Services\ExportTransformation\ConditionEvaluator;
use App\Services\ExportTransformation\TransformationEngine;

class ExportTransformationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Enregistrer OperationRegistry comme singleton
        $this->app->singleton(OperationRegistry::class, function ($app) {
            return new OperationRegistry();
        });

        // Enregistrer ConditionEvaluator comme singleton
        $this->app->singleton(ConditionEvaluator::class, function ($app) {
            return new ConditionEvaluator();
        });

        // Enregistrer TransformationEngine
        $this->app->singleton(TransformationEngine::class, function ($app) {
            return new TransformationEngine(
                $app->make(OperationRegistry::class),
                $app->make(ConditionEvaluator::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
