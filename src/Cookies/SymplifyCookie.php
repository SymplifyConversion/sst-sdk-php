<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK\Cookies;

use Psr\Log\LoggerInterface;
use SymplifyConversion\SSTSDK\Config\ProjectConfig;
use SymplifyConversion\SSTSDK\Config\VariationConfig;

/**
 * SymplifyCookie manages visitor and allocation information in our JSON cookie.
 */
final class SymplifyCookie
{

    private const JSON_COOKIE_NAME = 'sg_cookies';
    private const JSON_COOKIE_VERSION_KEY = '_g';
    private const JSON_COOKIE_VISITOR_ID_KEY = 'visid';
    private const JSON_COOKIE_PREVIEW_PROJECT_KEY = 'pmr';
    private const JSON_COOKIE_PREVIEW_VARIATION_KEY = 'pmv';
    private const SUPPORTED_JSON_COOKIE_VERSION = 1;

    /** @var array<string, mixed> */
    private array $underlying;
    private string $websiteID;

    /**
     * @param array<string, mixed> $underlying the JSON object hosting the cookie data.
     */
    public function __construct(string $websiteID, array $underlying)
    {
        $this->websiteID  = $websiteID;
        $this->underlying = $underlying;
    }

    /**
     * Initialize visitor data from the information in cookies.
     */
    public static function fromCookieJar(
        string $websiteID,
        CookieJar $cookies,
        LoggerInterface $logger
    ): ?SymplifyCookie
    {
        $cookieJSON = $cookies->getCookie(self::JSON_COOKIE_NAME);

        if (is_null($cookieJSON)) {
            // there is none, we'll create a valid one
            return new SymplifyCookie(
                $websiteID,
                array(
                    self::JSON_COOKIE_VERSION_KEY => self::SUPPORTED_JSON_COOKIE_VERSION,
                ),
            );
        }

        $sgCookies = json_decode($cookieJSON, true);

        if (is_null($sgCookies)) {
            $info = json_last_error_msg();
            $logger->error("cannot parse JSON cookie: $info");

            return null;
        }

        $cookieGeneration = $sgCookies[self::JSON_COOKIE_VERSION_KEY] ?? null;

        if (self::SUPPORTED_JSON_COOKIE_VERSION !== $cookieGeneration) {
            $logger->error(sprintf(
                "ID detection failed: unknown generation '%d' in cookie '%s'",
                $cookieGeneration ?? 'null',
                self::JSON_COOKIE_NAME
            ));

            return null;
        }

        return new SymplifyCookie($websiteID, $sgCookies);
    }

    /**
     * Persist the visitor information in the cookies.
     * Should be done at the end of the request when all mutations are complete.
     */
    public function saveTo(CookieJar $cookies): void
    {
        $jsonValue = json_encode($this->underlying);
        $cookies->setCookie(self::JSON_COOKIE_NAME, $jsonValue, 90);
    }

    /**
     * Get visitor ID from the cookie.
     *
     * If there is none, generate a one and store it.
     *
     * @throws \Exception
     */
    public function getVisitorID(?callable $idGenerator = null): ?string
    {
        $id = $this->getValue(self::JSON_COOKIE_VISITOR_ID_KEY);

        if (is_null($id)) {
            $id = is_null($idGenerator) ? self::newUUID() : $idGenerator();

            $this->setValue(self::JSON_COOKIE_VISITOR_ID_KEY, $id);
        }

        return $id;
    }

    public function getAllocationStatus(ProjectConfig $project): int
    {
        if (-1 === $this->getValue($project->id . "_ch")) {
            return AllocationStatus::NULL_ALLOCATION;
        }

        if ('array' === gettype($this->getValue($project->id . ""))) {
            return AllocationStatus::VARIATION_ALLOCATION;
        }

        return AllocationStatus::NONE;
    }

    public function getAllocation(ProjectConfig $project): ?VariationConfig
    {
        $allocated = $this->getValue($project->id. "");

        if ('array' === gettype($allocated)) {
            return $project->findVariationWithID($allocated[0]);
        }

        return null;
    }

    public function setAllocation(ProjectConfig $project, VariationConfig $variation): void
    {
        $projectID = $project->id;

        $aud_p = $this->getValue("aud_p");

        if ('array' !== gettype($aud_p)) {
            $aud_p = array();
        }

        if (!in_array($projectID, $aud_p, true)) {
            $aud_p[] = $projectID;
        }

        $this->setValue("aud_p", $aud_p);
        $this->setValue($projectID . "_ch", 1);
        $this->setValue($projectID . "", array($variation->id));
    }

    public function setNullAllocation(ProjectConfig $project): void
    {
        $this->setValue($project->id . "_ch", -1);
    }

    /**
     * PreviewData is used by the SDK when users are previewing tests without activating them.
     *
     * @return array<int>
     */
    public function getPreviewData(): ?array {
        $projectID = $this->getValue(self::JSON_COOKIE_PREVIEW_PROJECT_KEY);

        if(!is_int($projectID)){
            return null;
        }

        $variationID = $this->getValue(self::JSON_COOKIE_PREVIEW_VARIATION_KEY);

        if(!is_int($variationID)){
            return null;
        }

        return ['projectID' => $projectID, 'variationID' => $variationID];
    }

    // @phpstan-ignore-next-line
    private function getValue(string $key) // phpcs:ignore
    {
        return $this->underlying[$this->websiteID][$key] ?? null;
    }

    // @phpstan-ignore-next-line
    private function setValue(string $key, $newValue): void // phpcs:ignore
    {
        $this->underlying[$this->websiteID][$key] = $newValue;
    }

    /**
     * Returns a new v4 UUID.
     *
     * @throws \Exception if random number generation fails
     */
    private static function newUUID(): string
    {
        $buf = random_bytes(16);

        // this is a version 4 UUID
        $buf[6] = chr(ord($buf[6]) & 0x0f | 0x40);

        // ...of the "Leach-Salz" variant
        $buf[8] = chr(ord($buf[8]) & 0x3f | 0x80);

        return sprintf('%s%s-%s-%s-%s-%s%s%s', ...str_split(bin2hex($buf), 4));
    }

}
