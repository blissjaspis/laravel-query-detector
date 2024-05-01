<?php

namespace BlissJaspis\QueryDetector;

use Closure;

class QueryDetectorMiddleware
{

    public function __construct(
        public QueryDetector $detector
    ){}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $this->detector->isEnabled()) {
            return $next($request);
        }

        $this->detector->boot();

        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);

        // Modify the response to add the Debugbar
        $this->detector->output($request, $response);

        return $response;
    }
}
