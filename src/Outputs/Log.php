<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Outputs;

use BlissJaspis\QueryDetector\Contracts\Output;
use BlissJaspis\QueryDetector\Support\RequestContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log as LaravelLog;
use Symfony\Component\HttpFoundation\Response;

class Log implements Output
{
    public function boot(): void
    {
        //
    }

    public function output(Collection $detectedQueries, Response $response, ?Request $request = null): void
    {
        $this->log(RequestContext::formatLogHeader($request));

        foreach ($detectedQueries as $detectedQuery) {
            $logOutput = 'Model: '.$detectedQuery['model'].PHP_EOL;
            $logOutput .= 'Relation: '.$detectedQuery['relation'].PHP_EOL;
            $logOutput .= 'Num-Called: '.$detectedQuery['count'].PHP_EOL;
            $logOutput .= 'Call-Stack:'.PHP_EOL;

            foreach ($detectedQuery['sources'] as $source) {
                $logOutput .= '#'.$source['index'].' '.$source['name'].':'.$source['line'].PHP_EOL;
            }

            $this->log($logOutput);
        }
    }

    private function log(string $message): void
    {
        LaravelLog::channel(config('querydetector.log_channel'))->info($message);
    }
}
