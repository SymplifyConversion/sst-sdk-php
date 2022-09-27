<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use SymplifyConversion\SSTSDK\Audience\SymplifyAudience;
use SymplifyConversion\SSTSDK\Config\ClientConfig;
use SymplifyConversion\SSTSDK\Config\ProjectConfig;
use SymplifyConversion\SSTSDK\Config\RunState;
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

    /** @var ?string override the domain for which to write Symplify cookies */
    private ?string $cookieDomain;

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
        $this->cookieDomain     = $clientConfig->getCookieDomain();

        $this->config = null;
    }

    /**
     * @return static
     */
    public static function withDefaults(
        string $websiteID,
        bool $autoLoadConfig = true,
        ?string $cookieDomain = null
    ): self
    {
        $client = new self(new ClientConfig($websiteID, $cookieDomain));

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
     * @param array<mixed> $customAttributes
     * @return string|null the name of the current visitor's assigned variation,
     *         null if there is no matching project or no visitor ID was found.
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function findVariation(
        string $projectName,
        array $customAttributes = [],
        ?CookieJar $cookies = null
    ): ?string
    {
        try {
            if (!$this->config) {
                $this->logger->warning('findVariation called before config is available, returning null allocation');

                return null;
            }

            $foundProject = $this->findActiveProject($projectName, $this->config);

            if (is_null($foundProject)) {
                return null;
            }

            $cookies ??= new DefaultCookieJar($this->cookieDomain);

            if (2 === $this->config->privacyMode && '1' !== $cookies->getCookie('sg_optin')) {
                return null;
            }

            $sgCookies = SymplifyCookie::fromCookieJar($this->websiteID, $cookies, $this->logger);

            if (is_null($sgCookies)) {
                return null;
            }

            // 1. if previewing a project, handle and return early
            if(!is_null($sgCookies->getPreviewData())){
                return $this->handlePreview($sgCookies, $foundProject, $cookies, $customAttributes);
            }

            // 2. if we already have an allocation from a previous visit, use that and return early
            $persistedVariation = $this->findVariationInCookie($foundProject, $sgCookies);

            if (false !== $persistedVariation) {
                // if there was something persisted, we return and don't change anything in the cookie
                return is_null($persistedVariation) ? null : $persistedVariation->name;
            }

            // 3. no preview or variation from before: let's see if this project applies to the visitor
            if(!is_null($foundProject->audience_rules)) {
                $audience = new SymplifyAudience($foundProject->audience_rules, $this->logger);

                if(!$this->doesAudienceApply($audience, $customAttributes)){
                    return null;
                }
            }

            // 4. the project audience applies, lets allocate a variation.
            $allocatedVariation = $this->allocateVariation($foundProject, $sgCookies);

            $this->saveAllocation($foundProject, $allocatedVariation, $sgCookies);
            $sgCookies->saveTo($cookies);

            return is_null($allocatedVariation) ? null : $allocatedVariation->name;
        } catch (\Throwable $t) {
            $this->logger->error('findVariation failed: {exception}', ['exception' => $t]);

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

    private function findActiveProject(string $projectName, SymplifyConfig $config): ?ProjectConfig
    {
        $foundProject = $config->findProjectWithName($projectName);

        if (!$foundProject) {
            $this->logger->warning("project does not exist: '$projectName'");

            return null;
        }

        if (RunState::ACTIVE !== $foundProject->state) {
            return null;
        }

        return $foundProject;
    }

    /**
     * @return VariationConfig | false | null false if there is no allocation in the cookie
     */
    private function findVariationInCookie(ProjectConfig $project, SymplifyCookie $sgCookies) // phpcs:ignore
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

    /**
     * @throws \Exception
     */
    private function allocateVariation(ProjectConfig $project, SymplifyCookie $sgCookies): ?VariationConfig
    {
        $visitorID = $sgCookies->getVisitorID();

        if (is_null($visitorID)) {
            $this->logger->error('no visitor ID assigned, returning null allocation');

            return null;
        }

        return Allocation::findVariationForVisitor($project, $visitorID);
    }

    private function saveAllocation(
        ProjectConfig $project,
        ?VariationConfig $allocatedVariation,
        SymplifyCookie $sgCookies
    ): void
    {
        if (is_null($allocatedVariation)) {
            $sgCookies->setNullAllocation($project);
        } else {
            $sgCookies->setAllocation($project, $allocatedVariation);
        }
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

    /**
     * We are previewing a variation,
     * - trace the audience rules and give back in cookie
     * - override the allocation in the cookie for the previewer
     *
     * @param array<mixed> $audienceAttributes
     */
    private function handlePreview(
        SymplifyCookie $sgCookies,
        ProjectConfig $found_project,
        CookieJar $cookies,
        array $audienceAttributes
    ): ?string {
        if(isset($found_project->audience_rules)){
            $audience_rules = $found_project->audience_rules;

            if(count($audience_rules)){
                $audience = new SymplifyAudience($audience_rules, $this->logger);

                $audienceTrace = $audience->trace($audienceAttributes);

                if(is_string($audienceTrace)){
                    $this->logger->warning($audienceTrace);

                    return null;
                }

                $cookies->setCookie('sg_audience_trace', json_encode($audienceTrace), 1);

                if(!$this->doesAudienceApply($audience, $audienceAttributes)){
                    return null;
                }
            }
        }

        $variationID = $sgCookies->getPreviewData()['variationID'] ?? false;

        $variation = $variationID ? $found_project->findVariationWithID($variationID) : null;

        if($variation) {
            $sgCookies->setAllocation($found_project, $variation);
            $sgCookies->saveTo($cookies);
        }

        return $variation->name ?? null;
    }

    /**
     * @param array<mixed> $audienceAttributes
     */
    private function doesAudienceApply(SymplifyAudience $audience, array $audienceAttributes): ?bool {
        $audienceEval = $audience->eval($audienceAttributes);

        if(is_string($audienceEval)){
            $this->logger->warning('audience check failed: ' . $audienceEval);

            return null;
        }

        return $audienceEval;
    }

}
