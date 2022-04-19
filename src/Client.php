<?php

declare(strict_types=1);

namespace Symplify\SSTSDK;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
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

    /** @var string the ID of the website you run tests on */
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

    /** @var LoggerInterface a logger to collect messages from the SDK */
    private LoggerInterface $logger;

    /**
     * @throws \InvalidArgumentException if $cdnBaseURL is not a URL, or has no scheme or host.
     * @throws \InvalidArgumentException if an HTTP client is given without a corresponding request factory.
     */
    function __construct(ClientConfig $clientConfig)
    {
        $cdnBaseURL   = $clientConfig->getCdnBaseURL();
        $httpClient   = $clientConfig->getHttpClient();
        $httpRequests = $clientConfig->getHttpRequests();

        $parsedURL = parse_url($cdnBaseURL);

        if (!$parsedURL || !array_key_exists('scheme', $parsedURL) || !array_key_exists('host', $parsedURL)) {
            $message = "malformed \$cdnBaseURL ($cdnBaseURL), cannot create SDK client";

            throw new \InvalidArgumentException($message);
        }

        if (null !== $httpClient && null === $httpRequests) {
            throw new \InvalidArgumentException('HTTP client given without request factory');
        }

        $this->websiteID        = $clientConfig->getWebsiteID();
        $this->cdnBaseURL       = $cdnBaseURL;
        $this->httpClient       = $httpClient;
        $this->httpRequests     = $httpRequests;
        $this->maxDownloadBytes = $clientConfig->getMaxDownloadBytes();
        $this->logger           = new PrefixedLogger('SSTSDK: ', $clientConfig->getLogger());

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
            $this->logger->error('config fetch failed');

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
                $this->logger->error('findVariation called before config is available');

                return null;
            }

            $foundProject = $this->config->findProjectWithName($projectName);

            if (!$foundProject) {
                $this->logger->warning("project does not exist: '$projectName'");

                return null;
            }

            $visitorID      = Visitor::ensureVisitorID(new DefaultCookieJar(), $this->logger);
            $foundVariation = Allocation::findVariationForVisitor($foundProject, $visitorID);

            return $foundVariation->name;
        } catch (\Throwable $t) {
            $this->logger->error('findVariation failed', ['exception' => $t]);

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
            $this->logger->error('listProjects called before config is available');

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
            $this->logger->error('no config JSON to parse');

            return null;
        }

        $config = SymplifyConfig::fromJSON($response);

        if (!$config) {
            $info = JSON_ERROR_NONE === json_last_error() ? 'missing fields' : json_last_error_msg();
            $this->logger->error("could not parse config JSON: $info");
        }

        return $config;
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
                $this->logger->error('SST config is too large');

                return 1; // abort download
            }

            return 0; // CURL_PROGRESSFUNC_CONTINUE
        });

        $result = curl_exec($curl);
        $info   = curl_getinfo($curl);
        curl_close($curl);

        if (!$result || 200 !== $info['http_code']) {
            $this->logger->error("could not download latest config, server status: {$info['http_code']}");

            return null;
        }

        return $result;
    }

    private function downloadWithPsrHttpClient(string $url): ?string
    {
        $request  = $this->httpRequests->createRequest('GET', $url);
        $response = $this->httpClient->sendRequest($request);

        if (200 !== $response->getStatusCode()) {
            $this->logger->error("could not download latest config, server status: {$response->getStatusCode()}");

            return null;
        }

        if ($this->maxDownloadBytes < $response->getBody()->getSize()) {
            $this->logger->error('SST config is too large');

            return null;
        }

        // casting to read all the contents, we have already checked the size above
        return (string)$response->getBody();
    }

}
