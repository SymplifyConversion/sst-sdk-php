<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK\Config;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ClientConfig
{

    private const DEFAULT_CDN_BASEURL = 'https://cdn-sitegainer.com';

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

    /** @var LoggerInterface a logger to collect messages from the SDK */
    private LoggerInterface $logger;

    /** @var ?string override the domain for which to write Symplify cookies */
    private ?string $cookieDomain;

    /**
     * @param string      $websiteID your website ID
     * @param string|null $cookieDomain set to override the domain for Symplify cookie writing
     */
    function __construct(string $websiteID, ?string $cookieDomain = null)
    {
        $this->websiteID        = $websiteID;
        $this->cdnBaseURL       = self::DEFAULT_CDN_BASEURL;
        $this->maxDownloadBytes = 1024 * 1024;
        $this->httpClient       = null;
        $this->httpRequests     = null;
        $this->logger           = new NullLogger();
        $this->cookieDomain     = $cookieDomain;
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

    public function withLogger(LoggerInterface $newLogger): ClientConfig
    {
        $copy = clone $this;

        $copy->logger = $newLogger;

        return $copy;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function withCookieDomain(string $newCookieDomain): ClientConfig
    {
        $copy = clone $this;

        $copy->cookieDomain = $newCookieDomain;

        return $copy;
    }

    public function getCookieDomain(): ?string
    {
        return $this->cookieDomain;
    }

}
