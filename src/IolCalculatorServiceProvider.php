<?php

namespace Docratech\IolCalculator;

use Illuminate\Support\ServiceProvider;
use Docratech\IolCalculator\Services\IolCalculationService;
use Docratech\IolCalculator\Services\AdvancedIolCalculationService;

class IolCalculatorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(IolCalculationService::class, function ($app) {
            return new IolCalculationService();
        });

        $this->app->singleton(AdvancedIolCalculationService::class, function ($app) {
            return new AdvancedIolCalculationService();
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/iol-calculator.php', 'iol-calculator'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/iol-calculator.php' => config_path('iol-calculator.php'),
            ], 'iol-calculator-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'iol-calculator-migrations');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');
    }
}