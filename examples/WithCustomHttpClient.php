<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\Strategy\PublicCacheStrategy;
use Kevinrob\GuzzleCache\Storage\Psr16CacheStorage;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kodus\Cache\FileCache;

use Symplify\SSTSDK\Client as SymplifyClient;
use Symplify\SSTSDK\Config\ClientConfig as SymplifyClientConfig;

$websiteID  = "4711";
$cdnBaseURL = getenv('SSTSDK_CDN_BASEURL');

$cache           = new Psr16CacheStorage(new FileCache('/tmp/sstsdk-examples-httpcache', 500));
$cacheMiddleware = new CacheMiddleware(new PublicCacheStrategy($cache));
$stack           = HandlerStack::create();
$stack->push($cacheMiddleware, 'cache');

$httpClient   = new HttpClient(['handler' => $stack]);
$httpRequests = new HttpFactory();

$clientConfig = (new SymplifyClientConfig($websiteID))
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
