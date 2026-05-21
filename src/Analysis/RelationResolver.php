<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Analysis;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class RelationResolver
{
    public function resolveRelationName(Relation $relationObject, Collection $backtrace): string
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

    public function resolveRelatedModelClass(object $model, string $relationName): ?string
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
}
