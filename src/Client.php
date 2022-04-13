<?php

declare(strict_types=1);

namespace Symplify\SSTSDK;

use Psr\SimpleCache\CacheInterface;
use Symplify\SSTSDK\Config\SymplifyConfig;
use Symplify\SSTSDK\Cookies\DefaultCookieJar;

const DEFAULT_CDN_BASEURL = 'https://cdn-sitegainer.com';

/**
 * A client SDK for Symplify Server-Side Testing.
 *
 * The client maintains configuration for server-side tests for a website. It
 * also provides functions for allocating variations and assigning visitor IDs.
 */
final class Client
{

    /** @var string $websiteID the ID of the website you run tests on */
    private string $websiteID;

    /** @var CacheInterface a cache to keep SST configuration in */
    private CacheInterface $cache;

    /** @var string the base CDN URL from which to construct config URLs */
    private string $cdnBaseURL;

    /** @var ?SymplifyConfig the latest SST configuration we have seen */
    private ?SymplifyConfig $config;

    /**
     * @throws \InvalidArgumentException if $cdnBaseURL is not a URL, or has no scheme or host
     */
    function __construct(string $websiteID, CacheInterface $cache, string $cdnBaseURL = DEFAULT_CDN_BASEURL)
    {
        $parsedURL = parse_url($cdnBaseURL);

        if (!$parsedURL || !array_key_exists('scheme', $parsedURL) || !array_key_exists('host', $parsedURL)) {
            $message = "malformed \$cdnBaseURL ($cdnBaseURL), cannot create SDK client";

            throw new \InvalidArgumentException($message);
        }

        $this->websiteID  = $websiteID;
        $this->cache      = $cache;
        $this->cdnBaseURL = $cdnBaseURL;
        $this->config     = null;
    }

    /**
     * Returns the name of the variation the visitor is part of in the project with the given name.
     *
     * @return string|null the name of the current visitor's assigned variation,
     *         null if there is no matching project or no visitor ID was found.
     * @throws \Exception not yet implemented
     */
    public function findVariation(string $projectName): ?string
    {
        try {
            if (!$this->config) {
                error_log('[SSTSDK] findVariation called before config is available');

                return null;
            }

            $foundProject = $this->config->findProjectWithName($projectName);

            if (!$foundProject) {
                return null;
            }

            $visitorID      = Visitor::ensureVisitorID(new DefaultCookieJar());
            $foundVariation = Allocation::findVariationForVisitor($foundProject, $visitorID);

            return $foundVariation->name;
        } catch (\Throwable $t) {
            error_log('[SSTSDK] findVariation failed: ' . $t->getMessage());

            return null;
        }
    }

    function getConfigURL(): string
    {
        return "$this->cdnBaseURL/$this->websiteID/sstConfig.json";
    }

    /**
     * Temp cache testing function
     */
    public function hello(): string
    {
        $cacheKey  = 'hello_counter';
        $prevCount = $this->cache->get($cacheKey);
        $nextCount = ($prevCount ?? 0) + 1;

        $this->cache->set($cacheKey, $nextCount);

        return "Hello $this->websiteID World ($nextCount)";
    }

    /**
     * Download the current SST config.
     */
    function fetchConfig(): ?SymplifyConfig
    {
        $response = downloadURLContents($this->getConfigURL());

        if (!$response) {
            error_log('[SSTSDK] could not download latest config');

            return null;
        }

        return SymplifyConfig::fromJSON($response);
    }

    /**
     * Download the current SST config and cache it for re-use.
     */
    function loadConfig(): void
    {
        $config = $this->fetchConfig();

        if (!$config) {
            error_log('[SSTSDK] could not download latest config');

            return;
        }

        $this->config = $config;
    }

}

function downloadURLContents(string $url): ?string
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_URL, $url);
    $result = curl_exec($curl);
    curl_close($curl);

    return $result ?: null;
}
