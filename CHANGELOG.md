# Changelog

All notable changes to `laravel-query-detector` will be documented in this file

## Unreleased

### Added
- Per-route output configuration via `route_output` (URI patterns) and `route_names` (route name patterns)
- `querydetector.output` route middleware alias to set outputs per route (e.g. `querydetector.output:json,log`)
- GitHub Actions CI (tests, Pint, PHPStan)
- Composer `suggest` for optional Debugbar and Clockwork dependencies
- `excluded_paths` configuration for custom stack trace exclusions
- Tests for threshold, `app.debug`, outputs, and single event dispatch per request

### Changed
- Internal refactor: detection logic split into `QueryCollector`, `BacktraceParser`, `RelationResolver`, and `DetectedQueryFilter`; `QueryDetector` is now a thin orchestrator
- `QueryDetectorMiddleware` moved to `Middleware\QueryDetectorMiddleware` (update manual imports if you registered the middleware yourself)
- Relation names in detection output use Eloquent relation method names (e.g. `posts`) instead of related model class names
- `relatedModel` stores the related model class separately from `relation`
- `QueryDetected` event is dispatched once per HTTP request
- README and usage documentation completed

### Fixed
- PHPUnit configuration no longer references a missing `tests/Unit` directory
- Missing workbench route for the no-N+1 event test

## 2.0.0 - 2026-03-29

### Breaking Changes
- Drop support for Laravel 10 and 11
- Now requires **Laravel 12 and above**
- For Laravel 11 and below, use version 1.x: `composer require blissjaspis/laravel-query-detector:^1.0 --dev`

## 1.0.0 - 201X-XX-XX

- initial release
