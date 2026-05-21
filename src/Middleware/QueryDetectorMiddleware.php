<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Middleware;

use BlissJaspis\QueryDetector\QueryDetector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QueryDetectorMiddleware
{
    public function __construct(
        public QueryDetector $detector
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->detector->isEnabled()) {
            return $next($request);
        }

        $this->detector->boot();

        $response = $next($request);

        return $this->detector->output($request, $response);
    }
}
