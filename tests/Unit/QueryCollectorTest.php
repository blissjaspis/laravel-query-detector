<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Tests\Unit;

use BlissJaspis\QueryDetector\Analysis\BacktraceParser;
use BlissJaspis\QueryDetector\Analysis\RelationResolver;
use BlissJaspis\QueryDetector\Detection\QueryCollector;
use BlissJaspis\QueryDetector\Filtering\DetectedQueryFilter;
use BlissJaspis\QueryDetector\Tests\TestCase;
use Illuminate\Support\Collection;
use Workbench\App\Models\Author;

use function Orchestra\Testbench\workbench_path;

class QueryCollectorTest extends TestCase
{
    private QueryCollector $collector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('querydetector.threshold', 0);

        $this->collector = new QueryCollector(
            new BacktraceParser,
            new RelationResolver,
            new DetectedQueryFilter,
        );
    }

    public function test_it_skips_queries_without_eloquent_builder_in_backtrace(): void
    {
        $author = new Author;

        $this->collector->record(
            $this->makeQueryObject(),
            $this->makeBacktrace([
                [
                    'function' => 'getRelationValue',
                    'args' => ['profile'],
                    'object' => $author,
                ],
            ]),
        );

        $this->assertCount(0, $this->collector->getDetectedQueries());
    }

    public function test_it_skips_get_relation_value_without_args(): void
    {
        $author = new Author;

        $this->collector->record(
            $this->makeQueryObject(),
            $this->makeBacktrace([
                [
                    'function' => 'getRelationValue',
                    'object' => $author,
                ],
                [
                    'object' => $author->newQuery(),
                ],
            ]),
        );

        $this->assertCount(0, $this->collector->getDetectedQueries());
    }

    public function test_it_skips_get_relation_value_when_name_is_load_missing_without_relation_frame(): void
    {
        $author = new Author;

        $this->collector->record(
            $this->makeQueryObject(),
            $this->makeBacktrace([
                [
                    'function' => 'getRelationValue',
                    'args' => ['loadMissing'],
                    'object' => $author,
                ],
                [
                    'object' => $author->newQuery(),
                ],
            ]),
        );

        $this->assertCount(0, $this->collector->getDetectedQueries());
    }

    public function test_it_records_query_from_get_relation_value_trace(): void
    {
        $author = new Author;

        $this->collector->record(
            $this->makeQueryObject(),
            $this->makeBacktrace([
                [
                    'function' => 'getRelationValue',
                    'args' => ['profile'],
                    'object' => $author,
                ],
                [
                    'object' => $author->newQuery(),
                ],
            ]),
        );

        $queries = $this->collector->getDetectedQueries();

        $this->assertCount(1, $queries);
        $this->assertSame(Author::class, $queries[0]['model']);
        $this->assertSame('profile', $queries[0]['relation']);
    }

    public function test_it_prefers_relation_frame_over_load_missing_get_relation_value(): void
    {
        $author = new Author;
        $relation = $author->profile();

        $this->collector->record(
            $this->makeQueryObject(),
            $this->makeBacktrace([
                [
                    'object' => $relation,
                ],
                [
                    'function' => 'getRelationValue',
                    'args' => ['loadMissing'],
                    'object' => $author,
                ],
                [
                    'function' => 'loadMissing',
                    'args' => ['profile'],
                ],
                [
                    'object' => $author->newQuery(),
                ],
            ]),
        );

        $queries = $this->collector->getDetectedQueries();

        $this->assertCount(1, $queries);
        $this->assertSame(Author::class, $queries[0]['model']);
        $this->assertSame('profile', $queries[0]['relation']);
    }

    /**
     * @param  list<array<string, mixed>>  $frames
     */
    private function makeBacktrace(array $frames): Collection
    {
        $frames[] = [
            'file' => workbench_path('app/Http/Controllers/QueryController.php'),
            'class' => 'Workbench\App\Http\Controllers\QueryController',
            'line' => 17,
            'function' => 'nPlusQuery',
        ];

        return collect($frames);
    }

    private function makeQueryObject(): object
    {
        return (object) [
            'sql' => 'select * from profiles where author_id = ?',
            'time' => 0.5,
        ];
    }
}
