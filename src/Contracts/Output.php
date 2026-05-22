<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Contracts;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

interface Output
{
    public function boot(): void;

    public function output(Collection $detectedQueries, Response $response, ?Request $request = null): void;
}
