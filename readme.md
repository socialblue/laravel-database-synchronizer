# Laravel database synchronizer
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]
[![Scrutinizer Code Quality][ico-scrutinizer]][link-scrutinizer]

# Keep your production and development databases in sync

This package will completely synchronize the database specified as "from" and "to" in the config or through the command options.

Want to collaborate? Nice! Take a look at [contributing.md](contributing.md) to see a to do list.

## Installation

Via Composer

``` bash
$ composer require mtolhuijs/laravel-database-synchronizer
```

## Usage

This package comes with 1 command: 

- `php artisan db:sync` Synchronizes your "from" database with you're "to" database
```
db:sync
{ --from= : Synchronize data from this database instead of the one specified in config }
{ --to= : Synchronize data to this database instead of the one specified in config }
{ --t|tables=* : Only run for given table(s) }
{ --l|limit= : Limit query rows (defaults to 5000) }
```

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## Credits

- [Maarten Tolhuijs][link-author]
- [All Contributors][link-contributors]

## License

license. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/dt/mtolhuijs/laravel-database-synchronizer.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/mtolhuijs/laravel-database-synchronizer.svg?style=flat-square
[ico-travis]: https://api.travis-ci.com/mtolhuys/laravel-database-synchronizer.svg?branch=master
[ico-styleci]: https://styleci.io/repos/177603107/shield
[ico-scrutinizer]: https://scrutinizer-ci.com/g/mtolhuys/laravel-database-synchronizer/badges/quality-score.png?b=master

[link-packagist]: https://packagist.org/packages/mtolhuijs/laravel-database-synchronizer
[link-downloads]: https://packagist.org/packages/mtolhuijs/laravel-database-synchronizer
[link-travis]: https://travis-ci.org/mtolhuijs/laravel-database-synchronizer
[link-styleci]: https://styleci.io/repos/177603107

[link-author]: https://github.com/mtolhuys
[link-contributors]: ../../contributors
