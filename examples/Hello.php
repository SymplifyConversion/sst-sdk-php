<?php

require __DIR__ . '/vendor/autoload.php';

use SymplifyConversion\SSTSDK\Client as SymplifyClient;
use SymplifyConversion\SSTSDK\Config\ClientConfig;

$websiteID  = "4711";
$cdnBaseURL = getenv('SSTSDK_CDN_BASEURL');

$clientConfig = (new ClientConfig($websiteID))->withCdnBaseURL($cdnBaseURL);
$sdk          = new SymplifyClient($clientConfig);

$sdk->loadConfig();

foreach ($sdk->listProjects() as $projectName) {
    echo " * $projectName" . PHP_EOL;
    $variationName = $sdk->findVariation($projectName);
    echo "   - assigned variation: " . $variationName . PHP_EOL;
}
