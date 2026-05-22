---
title: Architecture
order: 3
---

## Overview

The package detects N+1 Eloquent relation queries during HTTP requests. Detection runs automatically via middleware when enabled (`APP_DEBUG` by default).

```
HTTP Request
    → QueryDetectorMiddleware
        → QueryDetector::boot()          # register DB::listen, boot outputs
        → application handles request
        → QueryDetector::output()        # filter, event, write outputs
```

## Source layout

| Class | Namespace | Responsibility |
|-------|-----------|----------------|
| `QueryDetectorMiddleware` | `Middleware` | Entry point: boot before request, output after response |
| `QueryDetector` | (root) | Orchestration: lifecycle, events, output drivers |
| `QueryCollector` | `Detection` | Record and aggregate relation queries per request |
| `BacktraceParser` | `Analysis` | Parse stack traces into user-facing sources |
| `RelationResolver` | `Analysis` | Resolve relation names and related model classes |
| `DetectedQueryFilter` | `Filtering` | Apply `except` whitelist and `threshold` |
| `Outputs\*` | `Outputs` | Write warnings (Alert, Log, Json, etc.) |

## Request flow

1. **Middleware** calls `QueryDetector::boot()` once per worker, then resets state on subsequent requests (Octane-safe).
2. **`DB::listen`** forwards each query to `QueryCollector::record()` with a debug backtrace.
3. **QueryCollector** checks whether the query came from lazy-loading a relation (via `Builder` / `Relation` in the stack), then aggregates by SQL + model + relation + source line.
4. **BacktraceParser** and **RelationResolver** extract call-site files and Eloquent relation method names.
5. After the response, **`QueryDetector::output()`** runs `DetectedQueryFilter`, dispatches `QueryDetected` once if issues exist, then applies configured output classes.

## Public API

These are the main extension points for package consumers:

- **Configuration** — `config/querydetector.php` (`enabled`, `threshold`, `except`, `output`, `route_output`, `route_names`, …)
- **Per-route outputs** — `Support\OutputResolver` picks drivers by URI, route name, or `querydetector.output` middleware
- **Event** — `Events\QueryDetected` for custom integrations (Sentry, Slack, etc.)
- **Output drivers** — implement `Contracts\Output` (`output(Collection $queries, Response $response, ?Request $request = null)`) and register the class in `output`

Internal classes (`QueryCollector`, `BacktraceParser`, etc.) are not part of the public API and may change without a major release.

## Middleware registration

**You do not need to register middleware yourself.** After `composer require`, `QueryDetectorServiceProvider` calls `$kernel->pushMiddleware()` so detection runs on every HTTP request.

Manual registration in `bootstrap/app.php` is **optional** and only relevant when:

- Package auto-discovery is disabled (`dont-discover`), or
- You intentionally manage middleware order/groups yourself and omit the provider’s registration.

If you already register it manually, use the current namespace:

```php
use BlissJaspis\QueryDetector\Middleware\QueryDetectorMiddleware;
```

Do not register it twice (provider + `bootstrap/app.php`), or middleware may run twice per request.

## Detection scope (HTTP vs the rest)

Detection is wired to the **HTTP request lifecycle**: `boot()` before the route runs, `output()` after the response is built. That matches how Laravel apps are usually debugged in the browser.

| Context | Automatic detection? | Notes |
|---------|---------------------|--------|
| HTTP (web, API, Inertia) | Yes | Via global middleware |
| Artisan commands | No | No HTTP request; see [usage — Beyond HTTP](usage.md#beyond-http-artisan-queues-and-jobs) |
| Queue jobs / `queue:work` | No | Same as above |
| PHPUnit without HTTP | No | Use feature tests that call routes, or wrap manually in local env |
| Octane / long-lived workers | Yes (per HTTP request) | State resets between requests; listener stays registered |

The core engine (`QueryCollector`, `DB::listen`) does not depend on HTTP itself—only the default **entry point** does. For commands and jobs you can call `QueryDetector::boot()` and `output()` yourself in local environments (documented in usage).
