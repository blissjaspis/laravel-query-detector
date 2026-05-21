<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Outputs;

use BlissJaspis\QueryDetector\Contracts\Output;
use DebugBar\DataCollector\MessagesCollector;
use Fruitcake\LaravelDebugbar\Facades\Debugbar as LaravelDebugbar;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class Debugbar implements Output
{
    protected MessagesCollector $collector;

    public function boot(): void
    {
        $this->collector = new MessagesCollector('N+1 Queries');

        if (! LaravelDebugbar::hasCollector($this->collector->getName())) {
            LaravelDebugbar::addCollector($this->collector);
        }
    }

    public function output(Collection $detectedQueries, Response $response): void
    {
        foreach ($detectedQueries as $detectedQuery) {
            $this->collector->addMessage(sprintf(
                'Model: %s => Relation: %s - You should add `with(%s)` to eager-load this relation.',
                $detectedQuery['model'],
                $detectedQuery['relation'],
                $detectedQuery['relation']
            ));
        }
    }
}
