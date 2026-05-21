<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector;

use BlissJaspis\QueryDetector\Events\QueryDetected;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class QueryDetector
{
    private Collection $queries;

    private bool $booted = false;

    private bool $eventDispatched = false;

    private ?Collection $detectedQueriesCache = null;

    public function __construct()
    {
        $this->resetQueries();
    }

    public function boot(): void
    {
        if ($this->booted) {
            $this->resetForRequest();

            return;
        }

        DB::listen(function ($query): void {
            $backtrace = collect(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 50));

            $this->logQuery($query, $backtrace);
        });

        foreach ($this->getOutputTypes() as $outputType) {
            app()->singleton($outputType);
            app($outputType)->boot();
        }

        $this->booted = true;
    }

    public function isEnabled(): bool
    {
        $configEnabled = value(config('querydetector.enabled'));

        if ($configEnabled === null) {
            $configEnabled = config('app.debug');
        }

        return (bool) $configEnabled;
    }

    public function logQuery(object $query, Collection $backtrace): void
    {
        $modelTrace = $backtrace->first(function ($trace) {
            return Arr::get($trace, 'object') instanceof Builder;
        });

        if ($modelTrace === null) {
            return;
        }

        $relation = $backtrace->first(function ($trace) {
            $object = Arr::get($trace, 'object');

            return Arr::get($trace, 'function') === 'getRelationValue'
                || $object instanceof Relation;
        });

        if (! is_array($relation) || ! isset($relation['object'])) {
            return;
        }

        if ($relation['object'] instanceof Relation) {
            $relationObject = $relation['object'];
            $model = $relationObject->getParent()::class;
            $relationName = $this->resolveRelationName($relationObject, $backtrace);
            $relatedModel = $relationObject->getRelated()::class;
        } else {
            $model = get_class($relation['object']);
            $relationName = $relation['args'][0];
            $relatedModel = $this->resolveRelatedModelClass($relation['object'], $relationName) ?? $relationName;
        }

        $sources = $this->findSource($backtrace);

        if ($sources === []) {
            return;
        }

        $key = md5($query->sql.$model.$relationName.$sources[0]['name'].$sources[0]['line']);

        $count = Arr::get($this->queries, $key.'.count', 0);
        $time = Arr::get($this->queries, $key.'.time', 0);

        $detectedQuery = [
            'count' => ++$count,
            'time' => $time + $query->time,
            'query' => $query->sql,
            'model' => $model,
            'relatedModel' => $relatedModel,
            'relation' => $relationName,
            'sources' => $sources,
        ];

        $this->queries[$key] = $detectedQuery;

        $this->detectedQueriesCache = null;
    }

    /**
     * @return list<array{index: int, name: string|null, line: int|string}>
     */
    protected function findSource(Collection $stack): array
    {
        $sources = [];

        foreach ($stack as $index => $trace) {
            $sources[] = $this->parseTrace($index, $trace);
        }

        return array_values(array_filter($sources));
    }

    /**
     * @param  array<string, mixed>  $trace
     * @return array{index: int, name: string|null, line: int|string}|false
     */
    public function parseTrace(int $index, array $trace): array|false
    {
        if (isset($trace['class'], $trace['file']) &&
            ! $this->fileIsInExcludedPath($trace['file'])
        ) {
            return [
                'index' => $index,
                'name' => $this->normalizeFilename($trace['file']),
                'line' => $trace['line'] ?? '?',
            ];
        }

        return false;
    }

    protected function fileIsInExcludedPath(string $file): bool
    {
        $excludedPaths = array_merge(
            [
                '/vendor/laravel/framework/src/Illuminate/Database',
                '/vendor/laravel/framework/src/Illuminate/Events',
            ],
            config('querydetector.excluded_paths', [])
        );

        $normalizedPath = str_replace('\\', '/', $file);

        foreach ($excludedPaths as $excludedPath) {
            if (str_contains($normalizedPath, $excludedPath)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeFilename(string $path): string
    {
        if (file_exists($path)) {
            $path = realpath($path) ?: $path;
        }

        return str_replace(base_path(), '', $path);
    }

    public function getDetectedQueries(): Collection
    {
        if ($this->detectedQueriesCache !== null) {
            return $this->detectedQueriesCache;
        }

        $queries = $this->filterQueries($this->queries->values());

        $this->detectedQueriesCache = $queries;

        return $queries;
    }

    protected function filterQueries(Collection $queries): Collection
    {
        $exceptions = config('querydetector.except', []);

        foreach ($exceptions as $parentModel => $relations) {
            foreach ($relations as $relation) {
                $queries = $queries->reject(function (array $query) use ($relation, $parentModel): bool {
                    if ($query['model'] !== $parentModel) {
                        return false;
                    }

                    return $query['relation'] === $relation
                        || $query['relatedModel'] === $relation;
                });
            }
        }

        return $queries
            ->where('count', '>', config('querydetector.threshold', 1))
            ->values();
    }

    protected function dispatchQueryDetectedEvent(Collection $queries): void
    {
        if ($queries->isEmpty() || $this->eventDispatched) {
            return;
        }

        event(new QueryDetected($queries));

        $this->eventDispatched = true;
    }

    /**
     * @return list<class-string>
     */
    protected function getOutputTypes(): array
    {
        $outputTypes = config('querydetector.output');

        if (! is_array($outputTypes)) {
            $outputTypes = [$outputTypes];
        }

        return $outputTypes;
    }

    protected function applyOutput(Collection $detectedQueries, Response $response): void
    {
        foreach ($this->getOutputTypes() as $type) {
            app($type)->output($detectedQueries, $response);
        }
    }

    public function output(mixed $request, Response $response): Response
    {
        $detectedQueries = $this->getDetectedQueries();

        $this->dispatchQueryDetectedEvent($detectedQueries);

        if ($detectedQueries->isNotEmpty()) {
            $this->applyOutput($detectedQueries, $response);
        }

        return $response;
    }

    protected function resolveRelationName(Relation $relationObject, Collection $backtrace): string
    {
        $getRelationValueTrace = $backtrace->first(function ($trace) {
            return Arr::get($trace, 'function') === 'getRelationValue'
                && isset($trace['args'][0]);
        });

        if (is_array($getRelationValueTrace)) {
            return $getRelationValueTrace['args'][0];
        }

        $relationMethodTrace = $backtrace
            ->filter(function ($trace) use ($relationObject) {
                $function = Arr::get($trace, 'function');
                $object = Arr::get($trace, 'object');

                return is_string($function)
                    && ! str_starts_with($function, '__')
                    && is_object($object)
                    && $object::class === $relationObject->getParent()::class
                    && method_exists($object, $function);
            })
            ->first();

        if (is_array($relationMethodTrace)) {
            return $relationMethodTrace['function'];
        }

        if (method_exists($relationObject, 'getRelationName')) {
            $relationName = $relationObject->getRelationName();

            if (is_string($relationName) && $relationName !== '') {
                return $relationName;
            }
        }

        return $this->guessRelationNameFromParent($relationObject);
    }

    protected function guessRelationNameFromParent(Relation $relationObject): string
    {
        $parent = $relationObject->getParent();
        $relatedClass = $relationObject->getRelated()::class;
        $relationClass = $relationObject::class;

        foreach (get_class_methods($parent) as $method) {
            if (str_starts_with($method, '__')) {
                continue;
            }

            try {
                $relation = $parent->{$method}();

                if ($relation::class === $relationClass && $relation->getRelated()::class === $relatedClass) {
                    return $method;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $relatedClass;
    }

    protected function resolveRelatedModelClass(object $model, string $relationName): ?string
    {
        if (! method_exists($model, $relationName)) {
            return null;
        }

        try {
            $relation = $model->{$relationName}();

            if (! $relation instanceof Relation) {
                return null;
            }

            return $relation->getRelated()::class;
        } catch (\Throwable) {
            return null;
        }
    }

    private function resetQueries(): void
    {
        $this->queries = Collection::make();
    }

    private function resetForRequest(): void
    {
        $this->resetQueries();
        $this->detectedQueriesCache = null;
        $this->eventDispatched = false;
    }
}
