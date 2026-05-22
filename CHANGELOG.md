# Changelog

All notable changes to `laravel-query-detector` will be documented in this file

## Unreleased

## 2.1.0 - 2026-05-22

### Added
- Per-route output configuration via `route_output` (URI patterns) and `route_names` (route name patterns)
- `querydetector.output` route middleware alias to set outputs per route (e.g. `querydetector.output:json,log`)
- Log output includes HTTP request context in the batch header: method, URI, and route name when available (e.g. `Detected N+1 Query [GET /posts] (route: posts.index)`)
- `Support\RequestContext` helper for formatting HTTP context in logs
- GitHub Actions CI (tests, Pint, PHPStan)
- Composer `suggest` for optional Debugbar and Clockwork dependencies
- `excluded_paths` configuration for custom stack trace exclusions
- Unit tests for `QueryCollector`, `RequestContext`, threshold, `app.debug`, outputs, and single event dispatch per request

### Changed
- Internal refactor: detection logic split into `QueryCollector`, `BacktraceParser`, `RelationResolver`, and `DetectedQueryFilter`; `QueryDetector` is now a thin orchestrator
- `QueryDetectorMiddleware` moved to `Middleware\QueryDetectorMiddleware` (update manual imports if you registered the middleware yourself)
- `Output` contract accepts an optional `?Request $request` argument so drivers can access HTTP context; custom output classes must update their `output()` signature
- `QueryDetector` passes the current HTTP request to output drivers when `output()` runs from middleware
- Relation names in detection output use Eloquent relation method names (e.g. `posts`) instead of related model class names
- `relatedModel` stores the related model class separately from `relation`
- `QueryDetected` event is dispatched once per HTTP request
- `QueryCollector` prefers `Relation` backtrace frames over `getRelationValue`, validates `getRelationValue` traces (model instance, relation name, skips `loadMissing` placeholder), and uses a clearer Eloquent `Builder` gate
- README and usage documentation completed

### Fixed
- `RelationResolver::guessRelationNameFromParent()` no longer invokes every model method to guess relation names (avoids side effects such as soft-deletes); uses reflection and safer `loadMissing` backtrace parsing instead
- PHPUnit configuration no longer references a missing `tests/Unit` directory
- Missing workbench route for the no-N+1 event test

## 2.0.0 - 2026-03-29

### Breaking Changes
- Drop support for Laravel 10 and 11
- Now requires **Laravel 12 and above**
- For Laravel 11 and below, use version 1.x: `composer require blissjaspis/laravel-query-detector:^1.0 --dev`

## 1.0.0 - 201X-XX-XX

- initial release
