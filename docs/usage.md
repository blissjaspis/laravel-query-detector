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
