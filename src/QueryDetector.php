<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector;

use BlissJaspis\QueryDetector\Analysis\BacktraceParser;
use BlissJaspis\QueryDetector\Detection\QueryCollector;
use BlissJaspis\QueryDetector\Events\QueryDetected;
use BlissJaspis\QueryDetector\Support\OutputResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class QueryDetector
{
    private bool $booted = false;

    private bool $eventDispatched = false;

    /** @var list<class-string> */
    private array $bootedOutputTypes = [];

    public function __construct(
        private QueryCollector $collector,
        private BacktraceParser $backtraceParser,
        private OutputResolver $outputResolver,
    ) {}

    public function boot(): void
    {
        if ($this->booted) {
            $this->resetForRequest();

            return;
        }

        DB::listen(function ($query): void {
            $backtrace = collect(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 50));

            $this->collector->record($query, $backtrace);
        });

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
        $this->collector->record($query, $backtrace);
    }

    /**
     * @param  array<string, mixed>  $trace
     * @return array{index: int, name: string|null, line: int|string}|false
     */
    public function parseTrace(int $index, array $trace): array|false
    {
        return $this->backtraceParser->parseTrace($index, $trace);
    }

    public function getDetectedQueries(): Collection
    {
        return $this->collector->getDetectedQueries();
    }

    public function output(mixed $request, Response $response): Response
    {
        $detectedQueries = $this->getDetectedQueries();

        $this->dispatchQueryDetectedEvent($detectedQueries);

        if ($detectedQueries->isNotEmpty()) {
            $this->applyOutput($detectedQueries, $response, $request);
        }

        return $response;
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
    protected function resolveOutputTypes(mixed $request): array
    {
        return $this->outputResolver->resolve($request instanceof Request ? $request : null);
    }

    /**
     * @param  list<class-string>  $outputTypes
     */
    protected function ensureOutputsBooted(array $outputTypes): void
    {
        foreach ($outputTypes as $outputType) {
            if (! app()->bound($outputType)) {
                app()->singleton($outputType);
            }

            if (! in_array($outputType, $this->bootedOutputTypes, true)) {
                app($outputType)->boot();
                $this->bootedOutputTypes[] = $outputType;
            }
        }
    }

    protected function applyOutput(Collection $detectedQueries, Response $response, mixed $request): void
    {
        $outputTypes = $this->resolveOutputTypes($request);

        $this->ensureOutputsBooted($outputTypes);

        $httpRequest = $request instanceof Request ? $request : null;

        foreach ($outputTypes as $type) {
            app($type)->output($detectedQueries, $response, $httpRequest);
        }
    }

    private function resetForRequest(): void
    {
        $this->collector->reset();
        $this->eventDispatched = false;
    }
}
