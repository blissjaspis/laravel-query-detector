<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Support;

use Illuminate\Http\Request;

class OutputResolver
{
    /**
     * @return list<class-string>
     */
    public function resolve(?Request $request = null): array
    {
        if ($request === null) {
            return $this->normalize(config('querydetector.output'));
        }

        if ($request->attributes->has('querydetector.output')) {
            return $this->normalize($request->attributes->get('querydetector.output'));
        }

        foreach (config('querydetector.route_names', []) as $pattern => $outputs) {
            if ($request->routeIs($pattern)) {
                return $this->normalize($outputs);
            }
        }

        foreach (config('querydetector.route_output', []) as $pattern => $outputs) {
            if ($request->is($pattern)) {
                return $this->normalize($outputs);
            }
        }

        return $this->normalize(config('querydetector.output'));
    }

    /**
     * @return list<class-string>
     */
    public function normalize(mixed $outputs): array
    {
        if (! is_array($outputs)) {
            $outputs = [$outputs];
        }

        return array_values($outputs);
    }
}
