<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Filtering;

use Illuminate\Support\Collection;

class DetectedQueryFilter
{
    public function apply(Collection $queries): Collection
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
}
