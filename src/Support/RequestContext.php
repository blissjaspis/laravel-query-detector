<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Support;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

final class RequestContext
{
    /**
     * @return array{method: string, uri: string, route: string|null}|null
     */
    public static function fromRequest(?Request $request): ?array
    {
        if (! $request instanceof Request) {
            return null;
        }

        $route = $request->route();

        return [
            'method' => $request->method(),
            'uri' => $request->getPathInfo(),
            'route' => $route instanceof Route ? $route->getName() : null,
        ];
    }

    public static function formatLogHeader(?Request $request): string
    {
        $context = self::fromRequest($request);

        if ($context === null) {
            return 'Detected N+1 Query';
        }

        $header = sprintf(
            'Detected N+1 Query [%s %s]',
            $context['method'],
            $context['uri'],
        );

        if ($context['route'] !== null && $context['route'] !== '') {
            $header .= sprintf(' (route: %s)', $context['route']);
        }

        return $header;
    }
}
