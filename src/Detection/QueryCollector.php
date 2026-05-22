<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Detection;

use BlissJaspis\QueryDetector\Analysis\BacktraceParser;
use BlissJaspis\QueryDetector\Analysis\RelationResolver;
use BlissJaspis\QueryDetector\Filtering\DetectedQueryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        if (! $this->backtraceContainsEloquentBuilder($backtrace)) {
            return;
        }

        $relationTrace = $this->findRelationTrace($backtrace);

        if ($relationTrace === null) {
            return;
        }

        if ($relationTrace['object'] instanceof Relation) {
            $relationObject = $relationTrace['object'];
            $model = $relationObject->getParent()::class;
            $relationName = $this->relationResolver->resolveRelationName($relationObject, $backtrace);
            $relatedModel = $relationObject->getRelated()::class;
        } else {
            /** @var Model $parentModel */
            $parentModel = $relationTrace['object'];
            $model = $parentModel::class;
            $relationName = $relationTrace['args'][0];
            $relatedModel = $this->relationResolver->resolveRelatedModelClass($parentModel, $relationName) ?? $relationName;
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

    private function backtraceContainsEloquentBuilder(Collection $backtrace): bool
    {
        return $backtrace->contains(function ($trace) {
            return Arr::get($trace, 'object') instanceof Builder;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findRelationTrace(Collection $backtrace): ?array
    {
        $relationTrace = $backtrace->first(function ($trace) {
            return Arr::get($trace, 'object') instanceof Relation;
        });

        if (is_array($relationTrace)) {
            return $relationTrace;
        }

        $relationTrace = $backtrace->first(function ($trace) {
            return is_array($trace) && $this->isGetRelationValueTrace($trace);
        });

        return is_array($relationTrace) ? $relationTrace : null;
    }

    /**
     * @param  array<string, mixed>  $trace
     */
    private function isGetRelationValueTrace(array $trace): bool
    {
        if (Arr::get($trace, 'function') !== 'getRelationValue') {
            return false;
        }

        $object = Arr::get($trace, 'object');

        if (! $object instanceof Model) {
            return false;
        }

        $relationName = Arr::get($trace, 'args.0');

        return is_string($relationName)
            && $relationName !== ''
            && $relationName !== 'loadMissing';
    }
}
