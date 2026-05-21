<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Tests;

use BlissJaspis\QueryDetector\QueryDetectorServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

use function Orchestra\Testbench\workbench_path;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase, WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Get package providers.
     *
     * @param  Application  $app
     * @return array<int, class-string<ServiceProvider>>
     */
    public function getPackageProviders($app)
    {
        return [
            QueryDetectorServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        tap($app['config'], function (Repository $config) {
            $config->set('database.default', 'testbench');
            $config->set('database.connections.testbench', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);

            $config->set('querydetector.enabled', true);

            // Setup queue database connections.
            $config->set('queue.batching.database', 'testbench');
            $config->set('queue.failed.database', 'testbench');
        });
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(workbench_path('database/migrations'));
    }

    protected function afterRefreshingDatabase()
    {
        $this->artisan('db:seed', ['--class' => 'Workbench\\Database\\Seeders\\DatabaseSeeder']);
    }
}
