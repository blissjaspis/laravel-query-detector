# Laravel N+1 Query Detector

The Laravel N+1 query detector helps you improve application performance by spotting relation queries that should be eager loaded. It monitors queries in real time during development and notifies you when an N+1 pattern is detected.

![Example alert](/asset/n+.png)

## History

This repository is a fork of [beyondcode/laravel-query-detector](https://github.com/beyondcode/laravel-query-detector). The original package was not updated for newer Laravel releases, so this fork keeps the tool compatible with current Laravel versions.

## Compatibility

### Version 2.x (current)

Compatible with **Laravel 12 and above** (PHP 8.2+).

### Version 1.x

For **Laravel 11 and below**:

```bash
composer require blissjaspis/laravel-query-detector:^1.0 --dev
```

## Installation

Install as a development dependency:

```bash
composer require blissjaspis/laravel-query-detector --dev
```

The service provider is registered automatically.

Publish configuration (optional):

```bash
php artisan vendor:publish --provider="BlissJaspis\QueryDetector\QueryDetectorServiceProvider"
```

## Usage

When `APP_DEBUG=true` (and `QUERY_DETECTOR_ENABLED` is not set to `false`), detection runs automatically on HTTP requests. No extra setup is required.

### Configuration

| Key | Default | Description |
|-----|---------|-------------|
| `enabled` | `null` | `null` uses `config('app.debug')`. Set `false` to disable explicitly. |
| `threshold` | `1` | Minimum number of identical relation queries before reporting. |
| `except` | `[]` | Whitelist per parent model. Use relation names (e.g. `posts`) or related model classes. |
| `excluded_paths` | `[]` | Extra stack trace paths to ignore. |
| `log_channel` | `daily` | Log channel when using the Log output. |
| `output` | `Alert`, `Log` | Default output classes (see below). |
| `route_output` | `[]` | URI pattern → output classes (e.g. `api/*` → Json + Log). |
| `route_names` | `[]` | Route name pattern → output classes (e.g. `api.*`). |
| `output_aliases` | built-in | Short names for `querydetector.output` route middleware. |

Environment variables:

- `QUERY_DETECTOR_ENABLED`
- `QUERY_DETECTOR_THRESHOLD`
- `QUERY_DETECTOR_LOG_CHANNEL`

### Output drivers

Configure `output` in `config/querydetector.php`:

| Class | Description |
|-------|-------------|
| `Outputs\Alert` | Browser `alert()` on HTML responses |
| `Outputs\Console` | Browser `console.warn()` on HTML responses |
| `Outputs\Log` | Writes to a Laravel log channel |
| `Outputs\Json` | Appends `warning_queries` to JSON API responses |
| `Outputs\Debugbar` | Requires [`fruitcake/laravel-debugbar`](https://github.com/fruitcake/laravel-debugbar) |
| `Outputs\Clockwork` | Requires [`itsgoingd/clockwork`](https://github.com/itsgoingd/clockwork) |

Optional integrations are listed under `composer suggest`.

### Custom handling

Listen for `BlissJaspis\QueryDetector\Events\QueryDetected` to send notifications (Sentry, Slack, etc.):

```php
use BlissJaspis\QueryDetector\Events\QueryDetected;

Event::listen(QueryDetected::class, function (QueryDetected $event) {
    foreach ($event->getQueries() as $query) {
        // $query['model'], $query['relation'], $query['relatedModel'], $query['count'], ...
    }
});
```

The event is dispatched once per HTTP request when issues are found.

### Scope and limitations

- Detection runs on **HTTP requests** via global middleware. **No manual middleware registration** is required—the service provider registers it automatically.
- **Artisan commands, queue jobs, and schedulers** are not covered unless you wrap them manually in local dev (see [usage — Beyond HTTP](docs/usage.md#beyond-http-artisan-queues-and-jobs)).
- **Inertia / SPA**: prefer `Log` + `Json` output; `Alert` only helps on full HTML page loads ([usage — SPA and Inertia](docs/usage.md#spa-and-inertiajs)).
- Intended for **local/staging development** only; keep it as a `--dev` dependency.
- For Octane and other long-lived workers, the detector resets its state between requests but keeps a single `DB::listen` registration per worker process.

More detail: [docs/usage.md](docs/usage.md).

Package internals and class responsibilities: [docs/architecture.md](docs/architecture.md).

## Development

```bash
composer install
composer test      # Run tests
composer lint      # Pint (dry-run) + PHPStan
composer format    # Apply Pint fixes
composer analyse   # PHPStan only
```

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md).

## Security

Report security issues to bliss@jaspis.me instead of using the public issue tracker.

## Credits

- [Bliss Jaspis](https://github.com/blissjaspis)
- [Marcel Pociot](https://github.com/mpociot)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). See [LICENSE](LICENSE.md).
