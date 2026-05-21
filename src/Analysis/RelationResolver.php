<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Analysis;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

class RelationResolver
{
    public function resolveRelationName(Relation $relationObject, Collection $backtrace): string
    {
        $getRelationValueTrace = $backtrace->first(function ($trace) {
            return Arr::get($trace, 'function') === 'getRelationValue'
                && isset($trace['args'][0]);
        });

        if (is_array($getRelationValueTrace)) {
            $relationName = $getRelationValueTrace['args'][0];

            if (is_string($relationName) && $relationName !== '' && $relationName !== 'loadMissing') {
                return $relationName;
            }
        }

        $loadMissingName = $this->resolveRelationNameFromLoadMissing($backtrace);

        if ($loadMissingName !== null) {
            return $loadMissingName;
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

    protected function resolveRelationNameFromLoadMissing(Collection $backtrace): ?string
    {
        $loadMissingTrace = $backtrace->first(function ($trace) {
            return Arr::get($trace, 'function') === 'loadMissing'
                && isset($trace['args'][0]);
        });

        if (! is_array($loadMissingTrace)) {
            return null;
        }

        $relationNames = $this->extractLoadMissingRelationNames($loadMissingTrace['args'][0]);

        return $relationNames[0] ?? null;
    }

    /**
     * @return list<string>
     */
    protected function extractLoadMissingRelationNames(mixed $argument): array
    {
        if (is_string($argument)) {
            return [$argument];
        }

        if (! is_array($argument)) {
            return [];
        }

        $names = [];

        foreach ($argument as $key => $value) {
            if (is_string($value)) {
                $names[] = $value;

                continue;
            }

            if (is_string($key)) {
                $names[] = $key;
            }
        }

        return $names;
    }

    protected function guessRelationNameFromParent(Relation $relationObject): string
    {
        $parent = $relationObject->getParent();
        $relatedClass = $relationObject->getRelated()::class;
        $relationClass = $relationObject::class;

        $candidates = $this->findRelationMethodCandidates($parent, $relationClass);

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        $conventionalName = $this->guessConventionalRelationName($relationObject, $relatedClass);

        if ($conventionalName !== null && method_exists($parent, $conventionalName)) {
            if ($candidates === [] || in_array($conventionalName, $candidates, true)) {
                return $conventionalName;
            }
        }

        return $relatedClass;
    }

    protected function guessConventionalRelationName(Relation $relationObject, string $relatedClass): ?string
    {
        $basename = class_basename($relatedClass);
        $snake = Str::snake($basename);

        if ($relationObject instanceof HasMany
            || $relationObject instanceof BelongsToMany
            || $relationObject instanceof MorphMany
            || $relationObject instanceof MorphToMany) {
            return Str::plural($snake);
        }

        return $snake;
    }

    /**
     * @return list<string>
     */
    protected function findRelationMethodCandidates(object $parent, string $relationClass): array
    {
        $reflection = new ReflectionClass($parent);
        $candidates = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $methodName = $method->getName();

            if (str_starts_with($methodName, '__')) {
                continue;
            }

            $returnTypeName = $this->resolveMethodReturnTypeName($method);

            if ($returnTypeName === null) {
                continue;
            }

            if (! is_a($returnTypeName, Relation::class, true)) {
                continue;
            }

            if ($returnTypeName !== $relationClass
                && ! is_subclass_of($returnTypeName, $relationClass)
                && ! is_subclass_of($relationClass, $returnTypeName)) {
                continue;
            }

            $candidates[] = $methodName;
        }

        return $candidates;
    }

    protected function resolveMethodReturnTypeName(ReflectionMethod $method): ?string
    {
        $returnType = $method->getReturnType();

        if ($returnType instanceof ReflectionNamedType && ! $returnType->isBuiltin()) {
            return $returnType->getName();
        }

        $docComment = $method->getDocComment();

        if (! is_string($docComment)) {
            return null;
        }

        if (preg_match('/@return\s+([\w\\\\]+)/', $docComment, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
