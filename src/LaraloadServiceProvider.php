<?php

namespace DarkGhostHunter\Laraload;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use DarkGhostHunter\Preloader\Preloader;
use DarkGhostHunter\Laraload\Http\Middleware\LaraloadMiddleware;

class LaraloadServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laraload.php', 'laraload');
    }

    /**
     * Bootstrap the application services.
     *
     * @param  \Illuminate\Contracts\Http\Kernel $kernel
     * @return void
     */
    public function boot(Kernel $kernel)
    {
        $kernel->pushMiddleware(LaraloadMiddleware::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/laraload.php' => config_path('laraload.php'),
            ], 'config');
        }
    }
}
