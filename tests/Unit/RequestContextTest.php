<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Tests\Unit;

use BlissJaspis\QueryDetector\Support\RequestContext;
use BlissJaspis\QueryDetector\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class RequestContextTest extends TestCase
{
    public function test_it_returns_null_for_non_http_request(): void
    {
        $this->assertNull(RequestContext::fromRequest(null));
    }

    public function test_it_formats_log_header_without_http_request(): void
    {
        $this->assertSame('Detected N+1 Query', RequestContext::formatLogHeader(null));
    }

    public function test_it_extracts_method_and_uri_from_request(): void
    {
        $request = Request::create('/authors/1', 'POST');

        $context = RequestContext::fromRequest($request);

        $this->assertSame([
            'method' => 'POST',
            'uri' => '/authors/1',
            'route' => null,
        ], $context);
    }

    public function test_it_includes_route_name_when_available(): void
    {
        $request = Request::create('/authors/1', 'GET');
        $route = new Route('GET', '/authors/{author}', []);
        $route->name('authors.show');
        $request->setRouteResolver(fn () => $route);

        $context = RequestContext::fromRequest($request);

        $this->assertSame('authors.show', $context['route']);
        $this->assertSame(
            'Detected N+1 Query [GET /authors/1] (route: authors.show)',
            RequestContext::formatLogHeader($request),
        );
    }
}
