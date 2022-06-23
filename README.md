Symplify Server-Side Testing SDK for PHP
========================================

This is the PHP implementation of the [Symplify Server-Side Testing SDK](./docs/Server-Side_Testing.md).

Due to the PHP concurrency model, the SDK is not keeping its own local cache for
the config, there simply is no great way to organize the invalidation logic when
the purpose of the caching is to keep the hot path fast.

Instead, we recommend you configure the HTTP client (can be injected to the SDK)
to do HTTP caching. See [Usage](#Usage) below.

Changes
=======

See [CHANGELOG.md](./CHANGELOG.md)

Requirements
============

* [PHP](https://www.php.net) 7.4 or later
* [Composer](https://getcomposer.org)

Installing
==========

Add the dependency through composer:

```
composer require "symplify-conversion/sst-sdk-php"
```

Usage
=====

```php
use SymplifyConversion\SSTSDK\Client as SymplifyClient;

// 1. configure the SDK and create an instance

$websiteID  = getenv('SYMPLIFY_WEBSITE_ID');
$sdk = SymplifyClient::withDefaults($websiteID);

// 2. Start off with the "default" values for everyone outside the test
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

Note that with the curl HTTP client you get no local caching, and thus depend
on our CDN for each request. You can use any HTTP client compatible with PSR-17
and PSR-18 instead of curl, and leverage their caching features. This is of
course a bit more involved:

```php
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\Strategy\PublicCacheStrategy;
use Kevinrob\GuzzleCache\Storage\Psr16CacheStorage;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kodus\Cache\FileCache;
use SymplifyConversion\SSTSDK\Client as SymplifyClient;
use SymplifyConversion\SSTSDK\Config\ClientConfig as SymplifyClientConfig;

// 1. configure the SDK and create an instance

$websiteID    = getenv('SSTSDK_WEBSITE_ID');

$cache           = new Psr16CacheStorage(new FileCache('/tmp/sstsdk-examples-httpcache', 500));
$cacheMiddleware = new CacheMiddleware(new PublicCacheStrategy($cache));
$stack           = HandlerStack::create();
$stack->push($cacheMiddleware, 'cache');

$clientConfig = (new SymplifyClientConfig($websiteID))
    ->withHttpClient(new HttpClient(['handler' => $stack]))
    ->withHttpRequests(new HttpFactory());

$sdk = new SymplifyClient($clientConfig);
// the constructor does not load config automatically
$sdk->loadConfig();

// steps 2 and 3 are the same as in the previous example
```

See more examples of code using the SDK in [examples](examples).

## SDK Development

See [CONTRIBUTING.md](./CONTRIBUTING.md) or [RELEASING.md](./RELEASING.md).
