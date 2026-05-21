<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Outputs;

use BlissJaspis\QueryDetector\Contracts\Output;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class Json implements Output
{
    public function boot(): void
    {
        //
    }

    public function output(Collection $detectedQueries, Response $response): void
    {
        if (! $response instanceof JsonResponse) {
            return;
        }

        $data = $response->getData(true);

        if (! is_array($data)) {
            $data = [$data];
        }

        $data['warning_queries'] = $detectedQueries->values()->all();

        $response->setData($data);
    }
}
