<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetQueryDetectorOutput
{
    public function handle(Request $request, Closure $next, string ...$outputs): Response
    {
        $resolved = array_map(function (string $output): string {
            $alias = config('querydetector.output_aliases.'.$output);

            return is_string($alias) ? $alias : $output;
        }, $outputs);

        $request->attributes->set('querydetector.output', $resolved);

        return $next($request);
    }
}
