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

if ($cdnBaseURL) {
    $sdk = new SSTSDK\Client($websiteID, $pool, $cdnBaseURL);
} else {
    $sdk = new SSTSDK\Client($websiteID, $pool);
}

// simple cache exercise
echo $sdk->hello() . PHP_EOL;

echo "Getting config from: ";
echo $sdk->getConfigURL() . " ... ";
$cfg = $sdk->fetchConfig();

if (!$cfg) {
    echo "no config downloaded!" . PHP_EOL;
    exit;
}

echo "OK" . PHP_EOL;

printf("Projects (as of %s)\n", date("c", $cfg->updated));

foreach ($cfg->projects as $proj) {
    echo " * $proj->id: $proj->name" . PHP_EOL;
}
