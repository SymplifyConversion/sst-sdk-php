<?php

declare(strict_types=1);

namespace Symplify\SSTSDK\Config;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

const DEFAULT_CDN_BASEURL = 'https://cdn-sitegainer.com';

final class ClientConfig
{

    /** @var string $websiteID the ID of the website you run tests on */
    private string $websiteID;

    /** @var string the base CDN URL from which to construct config URLs */
    private string $cdnBaseURL;

    /** @var ?ClientInterface an HTTP client to fetch SST configuration with */
    private ?ClientInterface $httpClient;

    /** @var ?RequestFactoryInterface a factory to make our HTTP requests */
    private ?RequestFactoryInterface $httpRequests;

    /** @var int the maximum JSON size in bytes to download and parse */
    private int $maxDownloadBytes;

    function __construct(string $websiteID)
    {
        $this->websiteID        = $websiteID;
        $this->cdnBaseURL       = DEFAULT_CDN_BASEURL;
        $this->maxDownloadBytes = 1024 * 1024;
        $this->httpClient       = null;
        $this->httpRequests     = null;
    }

    public function getWebsiteID(): string
    {
        return $this->websiteID;
    }

    public function withCdnBaseURL(string $newCdnBaseURL): ClientConfig
    {
        $copy = clone $this;

        $copy->cdnBaseURL = $newCdnBaseURL;

        return $copy;
    }

    public function getCdnBaseURL(): string
    {
        return $this->cdnBaseURL;
    }

    public function withMaxDownloadBytes(int $newMaxDownloadBytes): ClientConfig
    {
        $copy = clone $this;

        $copy->maxDownloadBytes = $newMaxDownloadBytes;

        return $copy;
    }

    public function getMaxDownloadBytes(): int
    {
        return $this->maxDownloadBytes;
    }

    public function withHttpClient(ClientInterface $newHttpClient): ClientConfig
    {
        $copy = clone $this;

        $copy->httpClient = $newHttpClient;

        return $copy;
    }

    public function getHttpClient(): ?ClientInterface
    {
        return $this->httpClient;
    }

    public function withHttpRequests(RequestFactoryInterface $newHttpRequests): ClientConfig
    {
        $copy = clone $this;

        $copy->httpRequests = $newHttpRequests;

        return $copy;
    }

    public function getHttpRequests(): ?RequestFactoryInterface
    {
        return $this->httpRequests;
    }

}
