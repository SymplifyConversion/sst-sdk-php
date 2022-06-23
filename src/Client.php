<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use SymplifyConversion\SSTSDK\Config\ClientConfig;
use SymplifyConversion\SSTSDK\Config\ProjectConfig;
use SymplifyConversion\SSTSDK\Config\ProjectState;
use SymplifyConversion\SSTSDK\Config\SymplifyConfig;
use SymplifyConversion\SSTSDK\Config\VariationConfig;
use SymplifyConversion\SSTSDK\Cookies\AllocationStatus;
use SymplifyConversion\SSTSDK\Cookies\CookieJar;
use SymplifyConversion\SSTSDK\Cookies\DefaultCookieJar;
use SymplifyConversion\SSTSDK\Cookies\SymplifyCookie;

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

        if (is_null($httpClient) !== is_null($httpRequests)) {
            throw new \InvalidArgumentException('HTTP client and request factory can only be used together');
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
     */
    public function findVariation(string $projectName, ?CookieJar $cookies = null): ?string
    {
        try {
            $foundProject = $this->findActiveProject($projectName);

            if (is_null($foundProject)) {
                return null;
            }

            $cookies ??= new DefaultCookieJar();

            $sgCookies = SymplifyCookie::fromCookieJar($this->websiteID, $cookies, $this->logger);

            if (is_null($sgCookies)) {
                return null;
            }

            $persistedVariation = $this->findVariationInCookie($foundProject, $sgCookies);

            if (false !== $persistedVariation) {
                // if there was something persisted, we return and don't change anything in the cookie
                return is_null($persistedVariation) ? null : $persistedVariation->name;
            }

            $allocatedVariation = $this->allocateVariation($foundProject, $sgCookies);

            if (is_null($allocatedVariation)) {
                $sgCookies->setNullAllocation($foundProject);
            } else {
                $sgCookies->setAllocation($foundProject, $allocatedVariation);
            }

            $sgCookies->saveTo($cookies);

            return is_null($allocatedVariation) ? null : $allocatedVariation->name;
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

    private function findActiveProject(string $projectName): ?ProjectConfig
    {
        if (!$this->config) {
            $this->logger->warning('findVariation called before config is available, returning null allocation');

            return null;
        }

        $foundProject = $this->config->findProjectWithName($projectName);

        if (!$foundProject) {
            $this->logger->warning("project does not exist: '$projectName'");

            return null;
        }

        if (ProjectState::ACTIVE !== $foundProject->state) {
            return null;
        }

        return $foundProject;
    }

    /**
     * @return VariationConfig | false | null false if there is no allocation in the cookie
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    private function findVariationInCookie(ProjectConfig $project, SymplifyCookie $sgCookies)
    {
        switch ($sgCookies->getAllocationStatus($project)) {
            case AllocationStatus::NULL_ALLOCATION:
                return null;

            case AllocationStatus::VARIATION_ALLOCATION:
                return $sgCookies->getAllocation($project);

            case AllocationStatus::NONE:
            default:
                return false;
        }
    }

    private function allocateVariation(ProjectConfig $project, SymplifyCookie $sgCookies): ?VariationConfig
    {
        $visitorID = $sgCookies->getVisitorID();

        if (is_null($visitorID)) {
            $this->logger->error('no visitor ID assigned, returning null allocation');

            return null;
        }

        return Allocation::findVariationForVisitor($project, $visitorID);
    }

    /**
     * Download the current SST config.
     */
    private function fetchConfig(): ?SymplifyConfig
    {
        $url      = $this->getConfigURL();
        $response = $this->httpClient ? $this->downloadWithPsrHttpClient($url) : $this->downloadWithCurl($url);

        if (!$response) {
            $this->logger->error('CDN response empty, no config JSON to parse');

            return null;
        }

        $config = SymplifyConfig::fromJSON($response);

        if (!$config) {
            $info = JSON_ERROR_NONE === json_last_error() ? 'missing fields' : json_last_error_msg();
            $this->logger->error("could not parse config JSON: $info");
        }

        return $config;
    }

    /**
     * Download the given URL using ext-curl.
     */
    private function downloadWithCurl(string $url): ?string
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_NOPROGRESS, false);
        // phpcs:ignore
        curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, function ($dltotal, $dlnow, $ultotal, $ulnow) {
            if ($this->maxDownloadBytes < $dltotal) {
                $this->logger->error('download aborted, SST config is too large');

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

        // if $result was false we already returned, and since we set CURLOPT_RETURNTRANSFER=true,
        // $result must be the string response
        return (string)$result;
    }

    /**
     * Download the given URL using the injected HTTP client and request factory.
     *
     * @psalm-suppress PossiblyNullReference we don't call this unless we checked the HTTP client exists
     */
    private function downloadWithPsrHttpClient(string $url): ?string
    {
        try {
            $request  = $this->httpRequests->createRequest('GET', $url);
            $response = $this->httpClient->sendRequest($request);
        } catch (\Throwable $t) {
            $this->logger->error("could not download latest config", ['exception' => $t]);

            return null;
        }

        if (200 !== $response->getStatusCode()) {
            $this->logger->error("could not download latest config, server status: {$response->getStatusCode()}");

            return null;
        }

        if ($this->maxDownloadBytes < $response->getBody()->getSize()) {
            $this->logger->error('download aborted, SST config is too large');

            return null;
        }

        // casting to read all the stream contents, we have already checked the size above
        return (string)$response->getBody();
    }

}
