<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class QueryDetectorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('querydetector.php'),
            ], 'config');
        }

        $this->registerMiddleware(QueryDetectorMiddleware::class);
    }

    public function register(): void
    {
        $this->app->singleton(QueryDetector::class);

        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'querydetector');
    }

    protected function registerMiddleware(string $middleware): void
    {
        $kernel = $this->app[Kernel::class];
        $kernel->pushMiddleware($middleware);
    }
}
