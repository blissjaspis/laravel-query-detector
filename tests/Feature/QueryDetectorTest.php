<?php

namespace BlissJaspis\QueryDetector\Feature\Tests;

use Illuminate\Support\Facades\Event;
use BlissJaspis\QueryDetector\QueryDetector;
use BlissJaspis\QueryDetector\Events\QueryDetected;
use BlissJaspis\QueryDetector\Tests\TestCase;
use Workbench\App\Models\Author;
use Workbench\App\Models\Comment;
use Workbench\App\Models\Post;

class QueryDetectorTest extends TestCase
{
    public function test_it_detects_n1_query_on_properties()
    {
        $this->get('/n-plus-query');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(1, $queries);

        $this->assertSame(Author::count(), $queries[0]['count']);
        $this->assertSame(Author::class, $queries[0]['model']);
        $this->assertSame('profile', $queries[0]['relation']);
    }

    public function test_it_detects_n1_query_on_multiple_requests()
    {
        // first request
        $this->get('/n-plus-query');
        $queries = app(QueryDetector::class)->getDetectedQueries();
        $this->assertCount(1, $queries);
        $this->assertSame(Author::count(), $queries[0]['count']);
        $this->assertSame(Author::class, $queries[0]['model']);
        $this->assertSame('profile', $queries[0]['relation']);

        // second request
        $this->get('/n-plus-query');
        $queries = app(QueryDetector::class)->getDetectedQueries();
        $this->assertCount(1, $queries);
        $this->assertSame(Author::count(), $queries[0]['count']);
        $this->assertSame(Author::class, $queries[0]['model']);
        $this->assertSame('profile', $queries[0]['relation']);
    }

    public function test_it_does_not_detect_a_false_n1_query_on_multiple_requests()
    {
        // first request
        $this->get('/not-n-plus-query');
        $this->assertCount(0, app(QueryDetector::class)->getDetectedQueries());

        // second request
        $this->get('/not-n-plus-query');
        $this->assertCount(0, app(QueryDetector::class)->getDetectedQueries());
    }

    public function test_it_ignores_eager_loaded_relationships()
    {
        $this->get('/not-n-plus-query');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(0, $queries);
    }

    public function test_it_detects_n1_queries_from_builder()
    {
        $this->get('/n-plus-query-from-builder');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(1, $queries);

        $this->assertSame(Author::count(), $queries[0]['count']);
        $this->assertSame(Author::class, $queries[0]['model']);
        $this->assertSame(Post::class, $queries[0]['relation']);
    }

    public function test_it_detects_all_n1_queries()
    {
        $this->get('/deteck-all-n-plus-query');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(2, $queries);

        $this->assertSame(Author::count(), $queries[0]['count']);
        $this->assertSame(Author::class, $queries[0]['model']);
        $this->assertSame(Post::class, $queries[0]['relation']);

        $this->assertSame(Post::count(), $queries[1]['count']);
        $this->assertSame(Post::class, $queries[1]['model']);
        $this->assertSame('author', $queries[1]['relation']);
    }

    public function test_it_detects_n1_queries_on_morph_relations()
    {
        $this->get('/deteck-n-plus-query-on-morph-relation');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(1, $queries);

        $this->assertSame(Post::count(), $queries[0]['count']);
        $this->assertSame(Post::class, $queries[0]['model']);
        $this->assertSame('comments', $queries[0]['relation']);
    }

    public function test_it_detects_n1_queries_on_morph_relations_with_builder()
    {
        $this->get('/deteck-n-plus-query-on-morph-relation-with-builder');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(1, $queries);

        $this->assertSame(Post::count(), $queries[0]['count']);
        $this->assertSame(Post::class, $queries[0]['model']);
        $this->assertSame(Comment::class, $queries[0]['relation']);
    }

    public function test_it_can_be_disabled()
    {
        $this->app['config']->set('querydetector.enabled', false);

        $this->get('/deteck-n-plus-query-on-morph-relation-with-builder');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(0, $queries);
    }

    public function test_it_ignores_whitelisted_relations()
    {
        $this->app['config']->set('querydetector.enabled', true);
        $this->app['config']->set('querydetector.except', [
            Post::class => [
                Comment::class
            ]
        ]);

        $this->get('/deteck-n-plus-query-on-morph-relation-with-builder');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(0, $queries);
    }

    public function test_it_ignores_whitelisted_relations_with_attributes()
    {
        $this->app['config']->set('querydetector.enabled', true);
        $this->app['config']->set('querydetector.except', [
            Post::class => [
                'comments'
            ]
        ]);

        $this->get('/deteck-n-plus-query-on-morph-relation');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(0, $queries);
    }

    public function test_it_ignores_redirects()
    {
        $this->get('/n-plus-query-ignores-redirects');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(1, $queries);
    }

    public function test_it_fires_an_event_if_detects_n1_query()
    {
        Event::fake();

        $this->get('/fire-an-event-if-detect-n-query');

        Event::assertDispatched(QueryDetected::class);
    }

    public function test_it_does_not_fire_an_event_if_there_is_no_n1_query()
    {
        Event::fake();

        $this->get('/not-fire-an-event-if-detect-no-n-query');

        Event::assertNotDispatched(QueryDetected::class);
    }

    public function test_it_uses_the_trace_line_to_detect_queries()
    {
        $this->get('/use-trace-line-to-detect-query');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(2, $queries);

        $this->assertSame(Author::count(), $queries[0]['count']);
        $this->assertSame(Author::class, $queries[0]['model']);
        $this->assertSame('profile', $queries[0]['relation']);
    }
}
