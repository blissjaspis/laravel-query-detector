<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Collection;

class QueryDetected
{
    use Dispatchable;

    public function __construct(
        public Collection $queries
    ) {}

    public function getQueries(): Collection
    {
        return $this->queries;
    }
}
