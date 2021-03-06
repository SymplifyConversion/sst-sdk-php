<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\Strategy\PublicCacheStrategy;
use Kevinrob\GuzzleCache\Storage\Psr16CacheStorage;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kodus\Cache\FileCache;

use SymplifyConversion\SSTSDK\Client as SymplifyClient;
use SymplifyConversion\SSTSDK\Config\ClientConfig as SymplifyClientConfig;

$websiteID  = "4711";
$cdnBaseURL = getenv('SSTSDK_CDN_BASEURL');

$cookieDomain = getenv('SSTSDK_COOKIE_DOMAIN');

if (!$cookieDomain) {
    $cookieDomain = ".localhost.test";
}

$cache           = new Psr16CacheStorage(new FileCache('/tmp/sstsdk-examples-httpcache', 500));
$cacheMiddleware = new CacheMiddleware(new PublicCacheStrategy($cache));
$stack           = HandlerStack::create();
$stack->push($cacheMiddleware, 'cache');

$httpClient   = new HttpClient(['handler' => $stack]);
$httpRequests = new HttpFactory();

$clientConfig = (new SymplifyClientConfig($websiteID, $cookieDomain))
    ->withCdnBaseURL($cdnBaseURL)
    ->withHttpClient($httpClient)
    ->withHttpRequests($httpRequests);

$sdk = new SymplifyClient($clientConfig);

$sdk->loadConfig();

foreach ($sdk->listProjects() as $projectName) {
    echo " * $projectName" . PHP_EOL;
    $variationName = $sdk->findVariation($projectName);
    echo "   - assigned variation: " . $variationName . PHP_EOL;
}
