<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Outputs;

use BlissJaspis\QueryDetector\Contracts\Output;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class Clockwork implements Output
{
    public function boot(): void
    {
        //
    }

    public function output(Collection $detectedQueries, Response $response): void
    {
        clock()->warning("{$detectedQueries->count()} N+1 queries detected:", $detectedQueries->toArray());
    }
}
