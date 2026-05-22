<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Tests\Feature;

use BlissJaspis\QueryDetector\Events\QueryDetected;
use BlissJaspis\QueryDetector\Outputs\Alert;
use BlissJaspis\QueryDetector\Outputs\Json;
use BlissJaspis\QueryDetector\Outputs\Log;
use BlissJaspis\QueryDetector\QueryDetector;
use BlissJaspis\QueryDetector\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log as LogFacade;
use Workbench\App\Models\Author;
use Workbench\App\Models\Comment;
use Workbench\App\Models\Post;

class QueryDetectorConfigTest extends TestCase
{
    public function test_it_respects_threshold_configuration(): void
    {
        $this->app['config']->set('querydetector.threshold', Author::count() + 1);

        $this->get('/n-plus-query');

        $this->assertCount(0, app(QueryDetector::class)->getDetectedQueries());
    }

    public function test_it_follows_app_debug_when_enabled_is_null(): void
    {
        $this->app['config']->set('querydetector.enabled', null);
        $this->app['config']->set('app.debug', false);

        $this->get('/n-plus-query');

        $this->assertCount(0, app(QueryDetector::class)->getDetectedQueries());
    }

    public function test_it_whitelists_relations_by_related_model_class(): void
    {
        $this->app['config']->set('querydetector.except', [
            Post::class => [
                Comment::class,
            ],
        ]);

        $this->get('/deteck-n-plus-query-on-morph-relation-with-builder');

        $this->assertCount(0, app(QueryDetector::class)->getDetectedQueries());
    }

    public function test_it_dispatches_query_detected_event_only_once_per_request(): void
    {
        Event::fake();

        $this->app['config']->set('querydetector.output', [
            Log::class,
            Alert::class,
        ]);

        $this->get('/fire-an-event-if-detect-n-query');

        Event::assertDispatched(QueryDetected::class, 1);
    }

    public function test_log_output_writes_detected_queries(): void
    {
        $output = new Log;

        $detectedQueries = collect([
            [
                'count' => 2,
                'time' => 1.5,
                'query' => 'select * from profiles',
                'model' => Author::class,
                'relatedModel' => 'profile',
                'relation' => 'profile',
                'sources' => [
                    ['index' => 0, 'name' => '/app/Http/Controllers/Example.php', 'line' => 10],
                ],
            ],
        ]);

        LogFacade::shouldReceive('channel')
            ->with(config('querydetector.log_channel'))
            ->andReturnSelf();

        LogFacade::shouldReceive('info')
            ->twice()
            ->andReturnNull();

        $output->output($detectedQueries, new Response);
    }

    public function test_log_output_includes_http_request_context(): void
    {
        $output = new Log;

        $detectedQueries = collect([
            [
                'count' => 2,
                'time' => 1.5,
                'query' => 'select * from profiles',
                'model' => Author::class,
                'relatedModel' => 'profile',
                'relation' => 'profile',
                'sources' => [
                    ['index' => 0, 'name' => '/app/Http/Controllers/Example.php', 'line' => 10],
                ],
            ],
        ]);

        $request = Request::create('/n-plus-query', 'GET');
        $route = new Route('GET', '/n-plus-query', []);
        $route->name('workbench.n-plus-query');
        $request->setRouteResolver(fn () => $route);

        LogFacade::shouldReceive('channel')
            ->with(config('querydetector.log_channel'))
            ->andReturnSelf();

        LogFacade::shouldReceive('info')
            ->once()
            ->with('Detected N+1 Query [GET /n-plus-query] (route: workbench.n-plus-query)')
            ->andReturnNull();

        LogFacade::shouldReceive('info')
            ->once()
            ->andReturnNull();

        $output->output($detectedQueries, new Response, $request);
    }

    public function test_log_output_includes_http_context_during_request(): void
    {
        $this->app['config']->set('querydetector.output', [
            Log::class,
        ]);

        $loggedMessages = [];

        LogFacade::shouldReceive('channel')
            ->with(config('querydetector.log_channel'))
            ->andReturnSelf();

        LogFacade::shouldReceive('info')
            ->andReturnUsing(function (string $message) use (&$loggedMessages): void {
                $loggedMessages[] = $message;
            });

        $this->get('/n-plus-query');

        $this->assertNotEmpty($loggedMessages);
        $this->assertStringContainsString('Detected N+1 Query [GET /n-plus-query]', $loggedMessages[0]);
    }

    public function test_json_output_adds_serializable_warning_queries(): void
    {
        $this->app['config']->set('querydetector.output', [
            Json::class,
        ]);

        $response = $this->getJson('/n-plus-query-json');

        $response->assertOk();
        $response->assertJsonStructure([
            'authors',
            'warning_queries' => [
                [
                    'model',
                    'relation',
                    'relatedModel',
                    'count',
                    'sources',
                ],
            ],
        ]);

        $this->assertSame('profile', $response->json('warning_queries.0.relation'));
    }
}
