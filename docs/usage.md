---
title: Usage
order: 2
---

## Usage

When `APP_DEBUG=true` and detection is not explicitly disabled, the query monitor is active automatically.

By default, the package shows a browser `alert()` when an N+1 query is found. You can publish the configuration and switch to Log, Console, JSON, Debugbar, or Clockwork output.

Publish configuration:

```bash
php artisan vendor:publish --provider="BlissJaspis\QueryDetector\QueryDetectorServiceProvider"
```

### Output options

| Output | Class |
|--------|-------|
| Alert | `\BlissJaspis\QueryDetector\Outputs\Alert::class` |
| Console | `\BlissJaspis\QueryDetector\Outputs\Console::class` |
| Log | `\BlissJaspis\QueryDetector\Outputs\Log::class` |
| JSON | `\BlissJaspis\QueryDetector\Outputs\Json::class` |
| Debugbar | `\BlissJaspis\QueryDetector\Outputs\Debugbar::class` (requires [fruitcake/laravel-debugbar](https://github.com/fruitcake/laravel-debugbar)) |
| Clockwork | `\BlissJaspis\QueryDetector\Outputs\Clockwork::class` (requires [itsgoingd/clockwork](https://github.com/itsgoingd/clockwork)) |

Example configuration:

```php
'output' => [
    \BlissJaspis\QueryDetector\Outputs\Log::class,
    \BlissJaspis\QueryDetector\Outputs\Alert::class,
],
```

### Output per route

Use `output` as the default. Override it for specific routes with `route_output` (URI patterns) or `route_names` (route name patterns). **First match wins** — put more specific rules first.

```php
'output' => [
    \BlissJaspis\QueryDetector\Outputs\Alert::class,
    \BlissJaspis\QueryDetector\Outputs\Log::class,
],

// URI patterns ($request->is())
'route_output' => [
    'api/*' => [
        \BlissJaspis\QueryDetector\Outputs\Json::class,
        \BlissJaspis\QueryDetector\Outputs\Log::class,
    ],
],

// Route name patterns ($request->routeIs())
'route_names' => [
    'inertia.*' => [
        \BlissJaspis\QueryDetector\Outputs\Json::class,
        \BlissJaspis\QueryDetector\Outputs\Log::class,
    ],
],
```

**Per-route in `routes/*.php`** (highest priority) using the `querydetector.output` middleware alias:

```php
Route::middleware('querydetector.output:json,log')->group(function () {
    Route::get('/reports', ...);
});

Route::get('/dashboard', ...)->middleware('querydetector.output:alert,log');
```

Aliases: `alert`, `console`, `log`, `json`, `clockwork`, `debugbar` (see `output_aliases` in config). You can also pass full class names.

**Priority (highest first):**

1. `querydetector.output` middleware / request attribute
2. `route_names`
3. `route_output`
4. `output` (default)

### Whitelisting relations

Use the relation method name in `except`. The related model class is also accepted:

```php
'except' => [
    Author::class => [
        'posts',
    ],
],
```

### Events

Listen to `\BlissJaspis\QueryDetector\Events\QueryDetected` for custom integrations (Sentry, Slack, etc.). The event fires once per request when N+1 queries are detected.

### Middleware

Registration is **automatic** via `QueryDetectorServiceProvider`. You do not need to add anything to `bootstrap/app.php`.

Only register `QueryDetectorMiddleware` yourself if you disabled package discovery or need explicit control over middleware order—and avoid duplicating what the provider already registers.

### Beyond HTTP: Artisan, queues, and jobs

N+1 detection does **not** run automatically for:

- `php artisan …` commands
- Queued jobs (`ShouldQueue`, `queue:work`, Horizon, etc.)
- Scheduled tasks (unless they run inside an HTTP request)

Those paths never pass through `QueryDetectorMiddleware`, so `boot()` and `output()` are never called.

**Practical options in development:**

1. **Reproduce via HTTP** — Trigger the same Eloquent code through a route or Inertia page while `APP_DEBUG=true`. This is the simplest approach.
2. **Log output** — Keep `Outputs\Log` in `output`; warnings are written to your log channel whenever `output()` runs (including manual wrapping below).
3. **Manual wrap (local only)** — At the start and end of a command or job:

```php
use BlissJaspis\QueryDetector\QueryDetector;
use Symfony\Component\HttpFoundation\Response;

$detector = app(QueryDetector::class);

if ($detector->isEnabled()) {
    $detector->boot();
}

// … command or job logic …

if ($detector->isEnabled()) {
    $detector->output(request(), new Response);
}
```

Use this only in `local` / `staging`. The `$request` argument is unused internally; an empty `Response` is enough for Log and `QueryDetected` event output. On subsequent runs in the same PHP process, `boot()` resets collector state automatically.

4. **Inspect results in code** — After `boot()` and your logic, call `$detector->getDetectedQueries()` and `dump()` / log in local env.

There is no first-class Artisan or queue integration in this package today; HTTP middleware remains the default and recommended path.

### SPA and Inertia.js

#### Inertia (Laravel + Vue/React/Svelte)

Inertia apps are still **HTTP-driven**: each visit and partial reload hits Laravel. Middleware runs on those requests.

| Output | First document load (HTML) | Inertia XHR (`X-Inertia: true`) |
|--------|---------------------------|--------------------------------|
| `Alert` / `Console` | Works if the response is HTML with `text/html` | Usually **skipped** (not HTML) |
| `Json` | Only if the response is a `JsonResponse` | Works when Laravel returns `JsonResponse` (typical Inertia JSON payload); adds `warning_queries` to the JSON body |
| `Log` | Always (server-side) | Always |
| `Debugbar` / `Clockwork` | When installed | When installed |

**Recommended for Inertia in local dev:**

```php
'output' => [
    \BlissJaspis\QueryDetector\Outputs\Log::class,
    \BlissJaspis\QueryDetector\Outputs\Json::class,
],
```

- Check `storage/logs` (or your `log_channel`) for every navigation.
- On Inertia JSON responses, inspect `warning_queries` in the Network tab (response JSON), or listen for `QueryDetected` and forward warnings to the frontend in dev only.

`Alert` alone is a poor fit for Inertia-heavy apps because most navigations are XHR, not full HTML reloads.

#### Decoupled SPA (separate API backend)

If the browser only talks to a **JSON API** (no Inertia, no server-rendered HTML):

- Use **`Json`** and/or **`Log`** — not `Alert` / `Console` (those target HTML pages).
- Read `warning_queries` from API responses in dev, or use the `QueryDetected` event server-side.
- Ensure detection is enabled only in non-production environments.

#### Optional: surface warnings in the frontend (dev only)

```php
// AppServiceProvider::boot() — local only
use BlissJaspis\QueryDetector\Events\QueryDetected;
use Inertia\Inertia;

Event::listen(QueryDetected::class, function (QueryDetected $event) {
    Inertia::share('nPlusOneWarnings', $event->getQueries()->values()->all());
});
```

Then read `nPlusOneWarnings` in a Vue/React layout component. Remove or guard this outside local development.
