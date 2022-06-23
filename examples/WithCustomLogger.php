<?php

require __DIR__ . '/vendor/autoload.php';

use SymplifyConversion\SSTSDK\Client as SymplifyClient;
use SymplifyConversion\SSTSDK\Config\ClientConfig;
use SymplifyConversion\SSTSDK\ErrorLogLogger;

$badJSON        = filter_input(INPUT_GET, 'badJSON', FILTER_VALIDATE_BOOLEAN);
$missingProject = filter_input(INPUT_GET, 'missingProject', FILTER_VALIDATE_BOOLEAN);

$websiteID  = $badJSON ? "42" : "4711";
$cdnBaseURL = getenv('SSTSDK_CDN_BASEURL');

$cookieDomain = getenv('SSTSDK_COOKIE_DOMAIN');

if (!$cookieDomain) {
    $cookieDomain = ".localhost.test";
}

$clientConfig = (new ClientConfig($websiteID, $cookieDomain))
    ->withLogger(new ErrorLogLogger())
    ->withCdnBaseURL($cdnBaseURL);

$sdk = new SymplifyClient($clientConfig);

$sdk->loadConfig();

foreach ($sdk->listProjects() as $projectName) {
    echo " * $projectName" . PHP_EOL;
    $variationName = $sdk->findVariation($projectName);
    echo "   - assigned variation: " . $variationName . PHP_EOL;
}

if ($missingProject) {
    $nonVariation = $sdk->findVariation('non-existant project');

    if ($nonVariation) {
        echo "unexpected variation found: " . $nonVariation . PHP_EOL;
    }
}
