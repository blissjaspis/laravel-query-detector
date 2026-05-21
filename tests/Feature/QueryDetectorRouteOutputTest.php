<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Tests\Feature;

use BlissJaspis\QueryDetector\Outputs\Alert;
use BlissJaspis\QueryDetector\Outputs\Json;
use BlissJaspis\QueryDetector\Outputs\Log;
use BlissJaspis\QueryDetector\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\QueryController;

class QueryDetectorRouteOutputTest extends TestCase
{
    public function test_route_output_overrides_default_for_matching_uri(): void
    {
        $this->app['config']->set('querydetector.output', [
            Alert::class,
        ]);

        $this->app['config']->set('querydetector.route_output', [
            'n-plus-query-json' => [
                Json::class,
            ],
        ]);

        $response = $this->getJson('/n-plus-query-json');

        $response->assertOk();
        $response->assertJsonStructure(['warning_queries']);
        $this->assertStringNotContainsString('alert(', (string) $response->getContent());
    }

    public function test_default_output_is_used_when_uri_does_not_match(): void
    {
        $this->app['config']->set('querydetector.output', [
            Alert::class,
        ]);

        $this->app['config']->set('querydetector.route_output', [
            'n-plus-query-json' => [
                Json::class,
            ],
        ]);

        $response = $this->get('/n-plus-query');

        $response->assertOk();
        $this->assertStringContainsString('alert(', (string) $response->getContent());
    }

    public function test_route_names_override_default_output(): void
    {
        Route::get('/named-n-plus-json', [QueryController::class, 'nPlusQueryJson'])
            ->name('api.n-plus-json');

        $this->app['config']->set('querydetector.output', [
            Alert::class,
        ]);

        $this->app['config']->set('querydetector.route_names', [
            'api.*' => [
                Json::class,
            ],
        ]);

        $response = $this->getJson('/named-n-plus-json');

        $response->assertOk();
        $response->assertJsonStructure(['warning_queries']);
    }

    public function test_request_attribute_override_takes_priority(): void
    {
        Route::get('/custom-output', [QueryController::class, 'nPlusQueryJson'])
            ->middleware('querydetector.output:json');

        $this->app['config']->set('querydetector.output', [
            Alert::class,
            Log::class,
        ]);

        $response = $this->getJson('/custom-output');

        $response->assertOk();
        $response->assertJsonStructure(['warning_queries']);
        $this->assertStringNotContainsString('alert(', (string) $response->getContent());
    }
}
