<?php

declare(strict_types=1);

namespace Symplify\SSTSDK;

use Psr\Cache\CacheItemPoolInterface;
use Symplify\SSTSDK\Config\SymplifyConfig;

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

    /** @var CacheItemPoolInterface a cache pool to keep SST configuration in */
    private CacheItemPoolInterface $cachePool;

    /** @var string the base CDN URL from which to construct config URLs */
    private string $cdnBaseURL;

    /**
     * @throws \InvalidArgumentException if $cdnBaseURL is not a URL, or has no scheme or host
     */
    function __construct(string $websiteID, CacheItemPoolInterface $cachePool, string $cdnBaseURL = DEFAULT_CDN_BASEURL)
    {
        $parsedURL = parse_url($cdnBaseURL);

        if (!$parsedURL || !array_key_exists('scheme', $parsedURL) || !array_key_exists('host', $parsedURL)) {
            $message = "malformed \$cdnBaseURL ($cdnBaseURL), cannot create SDK client";

            throw new \InvalidArgumentException($message);
        }

        $this->websiteID  = $websiteID;
        $this->cachePool  = $cachePool;
        $this->cdnBaseURL = $cdnBaseURL;
    }

    function getConfigURL(): string
    {
        return "$this->cdnBaseURL/$this->websiteID/sstConfig.json";
    }

    function fetchConfig(): ?SymplifyConfig
    {
        $response = $this->downloadURLContents($this->getConfigURL());

        if (!$response) {
            error_log('[SSTSDK] could not download latest config');

            return null;
        }

        return SymplifyConfig::fromJSON($response);
    }

    function downloadURLContents(string $url): ?string
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }

    public function hello(): string
    {
        $counter   = $this->cachePool->getItem('hello_counter');
        $prevCount = $counter->get();
        $nextCount = ($prevCount ?? 0) + 1;

        $counter->set($nextCount);

        if (!$this->cachePool->save($counter)) {
            return "could not persist cache update";
        }

        return "Hello $this->websiteID World ($nextCount)";
    }

}
