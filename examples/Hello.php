<?php

require __DIR__ . '/vendor/autoload.php';

use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem;

use Symplify\SSTSDK;

$filesystemAdapter = new Flysystem\Adapter\Local(__DIR__ . '/');
$filesystem        = new Flysystem\Filesystem($filesystemAdapter);
$pool              = new FilesystemCachePool($filesystem, '.cache-hello');

$websiteID  = getenv('SSTSDK_WEBSITE_ID');
$cdnBaseURL = getenv('SSTSDK_CDN_BASEURL');

$sdk = new SSTSDK\Client($websiteID, $pool, $cdnBaseURL);

echo $sdk->hello() . PHP_EOL;
