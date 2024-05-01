# Laravel N+1 Query Detector

The Laravel N+1 query detector helps you to increase your application's performance by reducing the number of queries it executes. This package monitors your queries in real-time, while you develop your application and notify you when you should add eager loading (N+1 queries).

![Example alert](/asset/n+.png)


## History

This repository is a fork of beyondcode/laravel-query-detector. Why forked? The fork was created because the original package hadn't been updated to support Laravel 11, leaving users uncertain about its future compatibility. By forking the repository, we aim to ensure that this essential package remains up-to-date and compatible with the latest Laravel versions.

## Installation

You can install the package via composer:

```bash
composer require blissjaspis/laravel-query-detector --dev
```

The package will automatically register itself.

## Documentation

On going...


### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email bliss@jaspis.me instead of using the issue tracker.

## Credits

- [Bliss Jaspis](https://github.com/blissjaspis)
- [Marcel Pociot](https://github.com/mpociot)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
