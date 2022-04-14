<?php

require __DIR__ . '/vendor/autoload.php';

use Symplify\SSTSDK\Client as SymplifyClient;
use Symplify\SSTSDK\Config\ClientConfig;

$websiteID  = getenv('SSTSDK_WEBSITE_ID');
$cdnBaseURL = getenv('SSTSDK_CDN_BASEURL');

$clientConfig = (new ClientConfig($websiteID))->withCdnBaseURL($cdnBaseURL);
$sdk          = new SymplifyClient($clientConfig);

$sdk->loadConfig();

foreach ($sdk->listProjects() as $projectName) {
    echo " * $projectName" . PHP_EOL;
    $variationName = $sdk->findVariation($projectName);
    echo "   - assigned variation: " . $variationName . PHP_EOL;
}
