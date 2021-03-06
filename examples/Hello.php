<?php

require __DIR__ . '/vendor/autoload.php';

use SymplifyConversion\SSTSDK\Client as SymplifyClient;
use SymplifyConversion\SSTSDK\Config\ClientConfig;

$websiteID  = "4711";
$cdnBaseURL = getenv('SSTSDK_CDN_BASEURL');
$cookieDomain = getenv('SSTSDK_COOKIE_DOMAIN');

if (!$cookieDomain) {
    $cookieDomain = ".localhost.test";
}

$clientConfig = (new ClientConfig($websiteID, $cookieDomain))->withCdnBaseURL($cdnBaseURL);
$sdk          = new SymplifyClient($clientConfig);

$sdk->loadConfig();
?>

<p>Project allocations:</p>
<dl>
    <?php foreach ($sdk->listProjects() as $projectName): // phpcs:ignore ?>
    <dt>project <?= $projectName ?></dt>
    <dd>variation <?= $sdk->findVariation($projectName) ?></dd>
    <?php endforeach ?>
</dl>
