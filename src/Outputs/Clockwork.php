<?php

namespace BlissJaspis\QueryDetector\Outputs;

use BlissJaspis\QueryDetector\Contracts\Output;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class Clockwork implements Output
{
    public function boot()
    {
        //
    }

    public function output(Collection $detectedQueries, Response $response)
    {
        clock()->warning("{$detectedQueries->count()} N+1 queries detected:", $detectedQueries->toArray());
    }
}
