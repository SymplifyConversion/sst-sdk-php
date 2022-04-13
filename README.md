Symplify Server-Side Testing SDK for PHP
========================================

This is the PHP implementation of the [Symplify Server-Side Testing SDK](./docs/Server-Side_Testing.md).

Requirements
============

* [PHP](https://www.php.net) 7.3 or later
* [Composer](https://getcomposer.org)

Installing
==========

Coming soon...

Usage
=====

This example uses `Flysystem` and `FilesystemCachePool`, but any PSR-16 compatible cache interface works.

```php
use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem;
use Symplify\SSTSDK;

// 1. configure the SDK and create an instance

$websiteID  = getenv('SYMPLIFY_WEBSITE_ID');

$filesystemAdapter = new Flysystem\Adapter\Local(__DIR__ . '/');
$filesystem        = new Flysystem\Filesystem($filesystemAdapter);
$pool              = new FilesystemCachePool($filesystem, '.cache-hello');

$sdk = new SSTSDK\Client($websiteID, $pool);

// 2. Start off with "default" values, for everyone outside the test
//    and in the original variation.

// assuming $sku is from the request, and you have a $catalog service to look up prices in
$price = $catalog->getCurrentPrice($sku);
$discounts = [];

// 3. Implement your test variation code. (This i in a test project called "discount")
//    `findVariation` will ensure the visitor ID is in cookies, and that the same
//    visitor gets the same variation every request you test.

switch ($sdk->findVariation('discount')) {
case 'huge':
    $discounts[] = 0.1; // perhaps a contrived example with this fallthrough
case 'small':
    $discounts[] = 0.1;
}

// assuming renderProductPage is how you present products
renderProductPage($sku, $price, $discounts);
```

The cache is used for persisting the SST configuration between requests to avoid having that extra latency in your hot
path.

See more examples of code using the SDK in [./examples](./examples).

SDK Development
===============

## Testing

The `examples` directory contains example scripts to show how to use the SDK, but they are also a nice way to test
locally during development.

```
# this starts php, serving the contents of examples, with some setup for the SDK
$ (cd examples; ./example-server.sh) &
$ curl http://localhost:8910/Hello.php
Hello 4711 World (1)
Getting config from: http://localhost:8911/4711/sstConfig.json ... OK
Projects (as of 2022-03-28T11:25:32+00:00)
 * 4711: discount
   - assigned variation: huge

$ curl http://localhost:8910/Hello.php
Hello 4711 World (2)
Getting config from: http://localhost:8911/4711/sstConfig.json ... OK
Projects (as of 2022-03-28T11:25:32+00:00)
 * 4711: discount
   - assigned variation: small
```

You can get stable responses by configuring curl for cookies e.g.
```
curl --cookie cookiejar.txt --cookie-jar cookiejar.txt http://localhost:8910/Hello.php
```

## Troubleshooting

If you get errors about classes not found when running tests, you might have lost the autoloader setup.
Run `composer install` again.

Beta Tasks
==========

- [x] hashing
- [x] fake config server for e2e testing
- [x] visitor ID assignment
- [x] variation assignment
- [ ] config state management
- [ ] use PSR-3 for logging
- [ ] use PSR-7 / PSR-18 for HTTP fetch
