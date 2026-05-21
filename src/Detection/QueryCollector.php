<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Detection;

use BlissJaspis\QueryDetector\Analysis\BacktraceParser;
use BlissJaspis\QueryDetector\Analysis\RelationResolver;
use BlissJaspis\QueryDetector\Filtering\DetectedQueryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class QueryCollector
{
    private Collection $queries;

    private ?Collection $detectedQueriesCache = null;

    public function __construct(
        private BacktraceParser $backtraceParser,
        private RelationResolver $relationResolver,
        private DetectedQueryFilter $filter,
    ) {
        $this->reset();
    }

    public function record(object $query, Collection $backtrace): void
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
            $relationName = $this->relationResolver->resolveRelationName($relationObject, $backtrace);
            $relatedModel = $relationObject->getRelated()::class;
        } else {
            $model = get_class($relation['object']);
            $relationName = $relation['args'][0];
            $relatedModel = $this->relationResolver->resolveRelatedModelClass($relation['object'], $relationName) ?? $relationName;
        }

        $sources = $this->backtraceParser->findSources($backtrace);

        if ($sources === []) {
            return;
        }

        $key = md5($query->sql.$model.$relationName.$sources[0]['name'].$sources[0]['line']);

        $count = Arr::get($this->queries, $key.'.count', 0);
        $time = Arr::get($this->queries, $key.'.time', 0);

        $this->queries[$key] = [
            'count' => ++$count,
            'time' => $time + $query->time,
            'query' => $query->sql,
            'model' => $model,
            'relatedModel' => $relatedModel,
            'relation' => $relationName,
            'sources' => $sources,
        ];

        $this->detectedQueriesCache = null;
    }

    public function getDetectedQueries(): Collection
    {
        if ($this->detectedQueriesCache !== null) {
            return $this->detectedQueriesCache;
        }

        $this->detectedQueriesCache = $this->filter->apply($this->queries->values());

        return $this->detectedQueriesCache;
    }

    public function reset(): void
    {
        $this->queries = Collection::make();
        $this->detectedQueriesCache = null;
    }
}
