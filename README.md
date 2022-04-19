Symplify Server-Side Testing SDK for PHP
========================================

This is the PHP implementation of the [Symplify Server-Side Testing SDK](./docs/Server-Side_Testing.md).

Due to the PHP concurrency model, the SDK is not keeping its own local cache for
the config, there simply is no great way to organize the invalidation logic when
the purpose of the caching is to keep the hot path fast.

Instead, we recommend you configure the HTTP client (can be injected to the SDK)
to do HTTP caching. See [Usage](#Usage) below.

Requirements
============

* [PHP](https://www.php.net) 7.4 or later
* [Composer](https://getcomposer.org)

Installing
==========

Coming soon...

Usage
=====

Using ext-curl for HTTP requests:

```php
[...]
use Symplify\SSTSDK\Client as SymplifyClient;
[...]

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
[...]
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\Strategy\PublicCacheStrategy;
use Kevinrob\GuzzleCache\Storage\Psr16CacheStorage;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kodus\Cache\FileCache;
use Symplify\SSTSDK\Client as SymplifyClient;
use Symplify\SSTSDK\Config\ClientConfig as SymplifyClientConfig;
[...]

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

See more examples of code using the SDK in [./examples](./examples).

SDK Development
===============

## Running CI locally

You can use [act](https://github.com/nektos/act) to execute the GitHub workflow
locally. It requires Docker.

```shell
$ act -P ubuntu-latest=shivammathur/node:latest
```

## Local Testing

The `examples` directory contains example scripts to show how to use the SDK, but they are also a nice way to test
locally during development.

```
# this starts php, serving the contents of examples, with some setup for the SDK
$ (cd examples; ./example-server.sh) &
$ curl http://localhost:8910/WithCustomHttpClient.php
[Wed Apr 13 18:51:56 2022] 127.0.0.1:52273 Accepted
[Wed Apr 13 18:51:56 2022] 127.0.0.1:52274 Accepted
[Wed Apr 13 18:51:56 2022] 127.0.0.1:52274 [INFO] ExamplesCDN: GET /4711/sstConfig.json
[Wed Apr 13 18:51:56 2022] 127.0.0.1:52274 Closing
[Wed Apr 13 18:51:56 2022] 127.0.0.1:52273 [200]: GET /WithCustomHttpClient.php
[Wed Apr 13 18:51:56 2022] 127.0.0.1:52273 Closing
 * discount
   - assigned variation: original

$ curl http://localhost:8910/WithCustomHttpClient.php
[Wed Apr 13 18:51:58 2022] 127.0.0.1:52275 Accepted
[Wed Apr 13 18:51:58 2022] 127.0.0.1:52275 [200]: GET /WithCustomHttpClient.php
[Wed Apr 13 18:51:58 2022] 127.0.0.1:52275 Closing
 * discount
   - assigned variation: original

```

You can get stable variation allocations by configuring curl for cookies e.g.
```
curl --cookie cookiejar.txt --cookie-jar cookiejar.txt http://localhost:8910/Hello.php
```

## Troubleshooting

If you get errors about classes not found when running tests, you might have lost the autoloader setup.
Run `composer install` again.
