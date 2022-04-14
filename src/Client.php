<?php

declare(strict_types=1);

namespace Symplify\SSTSDK;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Symplify\SSTSDK\Config\ClientConfig;
use Symplify\SSTSDK\Config\SymplifyConfig;
use Symplify\SSTSDK\Cookies\DefaultCookieJar;

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

    /** @var string the base CDN URL from which to construct config URLs */
    private string $cdnBaseURL;

    /** @var ?ClientInterface an HTTP client to fetch SST configuration with */
    private ?ClientInterface $httpClient;

    /** @var ?RequestFactoryInterface a factory to make our HTTP requests */
    private ?RequestFactoryInterface $httpRequests;

    /** @var int the maximum JSON size in bytes to download and parse */
    private int $maxDownloadBytes;

    /** @var ?SymplifyConfig the latest SST configuration we have seen */
    private ?SymplifyConfig $config;

    /**
     * @throws \InvalidArgumentException if $cdnBaseURL is not a URL, or has no scheme or host.
     * @throws \InvalidArgumentException if an HTTP client is given without a corresponding request factory.
     */
    function __construct(ClientConfig $clientConfig)
    {
        $websiteID        = $clientConfig->getWebsiteID();
        $maxDownloadBytes = $clientConfig->getMaxDownloadBytes();
        $httpRequests     = $clientConfig->getHttpRequests();
        $cdnBaseURL       = $clientConfig->getCdnBaseURL();
        $httpClient       = $clientConfig->getHttpClient();

        $parsedURL = parse_url($cdnBaseURL);

        if (!$parsedURL || !array_key_exists('scheme', $parsedURL) || !array_key_exists('host', $parsedURL)) {
            $message = "malformed \$cdnBaseURL ($cdnBaseURL), cannot create SDK client";

            throw new \InvalidArgumentException($message);
        }

        if (null !== $httpClient && null === $httpRequests) {
            throw new \InvalidArgumentException('HTTP client given without request factory');
        }

        $this->websiteID        = $websiteID;
        $this->cdnBaseURL       = $cdnBaseURL;
        $this->httpClient       = $httpClient;
        $this->httpRequests     = $httpRequests;
        $this->maxDownloadBytes = $maxDownloadBytes;

        $this->config = null;
    }

    public static function withDefaults(string $websiteID, bool $autoLoadConfig = true): self
    {
        $client = new self(new ClientConfig($websiteID));

        if ($autoLoadConfig) {
            $client->loadConfig();
        }

        return $client;
    }

    function getConfigURL(): string
    {
        return "$this->cdnBaseURL/$this->websiteID/sstConfig.json";
    }

    /**
     * Set the current SST config from CDN.
     */
    public function loadConfig(): void
    {
        $config = $this->fetchConfig();

        if (!$config) {
            error_log('[SSTSDK] could not download latest config');

            return;
        }

        $this->config = $config;
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

    /**
     * Get the names of all projects in the current SST config.
     *
     * @return array<string>
     */
    public function listProjects(): array
    {
        if (!$this->config) {
            error_log('[SSTSDK] listProjects called before config is available');

            return [];
        }

        $projectNames = [];

        foreach ($this->config->projects as $project) {
            $projectNames[] = $project->name;
        }

        return $projectNames;
    }

    /**
     * Download the current SST config.
     */
    private function fetchConfig(): ?SymplifyConfig
    {
        $url      = $this->getConfigURL();
        $response = $this->httpClient ? $this->downloadWithPsrHttpClient($url) : $this->downloadWithCurl($url);

        if (!$response) {
            error_log('[SSTSDK] no config JSON to parse');

            return null;
        }

        return SymplifyConfig::fromJSON($response);
    }

    private function downloadWithCurl(string $url): ?string
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_NOPROGRESS, false);
        // phpcs:ignore
        curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, function ($dltotal, $dlnow, $ultotal, $ulnow) {
            if ($this->maxDownloadBytes < $dltotal) {
                error_log('[SSTSDK] SST config is too big');

                return 1; // abort download
            }

            return 0; // CURL_PROGRESSFUNC_CONTINUE
        });

        $result = curl_exec($curl);
        $info   = curl_getinfo($curl);
        curl_close($curl);

        if (!$result || 200 !== $info['http_code']) {
            error_log('[SSTSDK] could not download latest config');

            return null;
        }

        return $result;
    }

    private function downloadWithPsrHttpClient(string $url): ?string
    {
        $request  = $this->httpRequests->createRequest('GET', $url);
        $response = $this->httpClient->sendRequest($request);

        if (200 !== $response->getStatusCode()) {
            error_log('[SSTSDK] could not download latest config');

            return null;
        }

        if ($this->maxDownloadBytes < $response->getBody()->getSize()) {
            error_log('[SSTSDK] SST config is too big');

            return null;
        }

        return $response->getBody()->getContents();
    }

}
