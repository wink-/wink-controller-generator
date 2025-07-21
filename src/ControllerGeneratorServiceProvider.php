<?php

namespace Wink\ControllerGenerator;

use Illuminate\Support\ServiceProvider;
use Wink\ControllerGenerator\Commands\GenerateControllerCommand;

class ControllerGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/wink-controllers.php',
            'wink-controllers'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/wink-controllers.php' => config_path('wink-controllers.php'),
            ], 'wink-controllers-config');

            $this->publishes([
                __DIR__.'/../stubs' => resource_path('stubs/wink/controllers'),
            ], 'wink-controllers-stubs');

            $this->commands([
                GenerateControllerCommand::class,
            ]);
        }
    }
}