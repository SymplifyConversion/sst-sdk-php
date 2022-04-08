Symplify Server-Side Testing SDK for PHP
========================================

This is the PHP implementation of the [Symplify Server-Side Testing
SDK](./docs/Server-Side_Testing.md).

Requirements
============

* [PHP](https://www.php.net) 7.3 or later
* [Composer](https://getcomposer.org)

Installing
==========

Coming soon...

Usage
=====

See examples of code using the SDK in [./examples](./examples). You can serve
them locally by running `composer serve` in that directory.

More info coming soon...

SDK Development
===============

## Testing

The `examples` directory contains example scripts to show how to use the SDK,
but they are also a nice way to test locally during development.

```
$ cd examples
$ SSTSDK_WEBSITE_ID=4711 php -q -S localhost:8910 &
$ curl http://localhost:8910/Hello.php
Hello 4711 World (1)

$ curl http://localhost:8910/Hello.php
Hello 4711 World (2)

```

## Troubleshooting

If you get errors about classes not found when running tests, you might have
lost the autoloader setup. Run `composer install` again.

Beta Tasks
==========

- [x] hashing
- [ ] fake config server for e2e testing
- [ ] visitor ID assignment
- [ ] variation assignment
- [ ] config state management
