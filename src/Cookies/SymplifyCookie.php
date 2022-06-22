<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK\Cookies;

use Psr\Log\LoggerInterface;

/**
 * SymplifyCookie manages visitor and allocation information in our JSON cookie.
 */
final class SymplifyCookie
{

    private const JSON_COOKIE_NAME = 'sg_cookies';
    private const JSON_COOKIE_VERSION_KEY = '_g';
    private const JSON_COOKIE_VISITOR_ID_KEY = 'visid';
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
                [
                    self::JSON_COOKIE_VERSION_KEY => self::SUPPORTED_JSON_COOKIE_VERSION,
                ],
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
        $cookies->setCookie(self::JSON_COOKIE_NAME, $jsonValue);
    }

    /**
     * @throws \Exception
     */
    public function getVisitorID(?callable $idGenerator = null): ?string
    {
        $id = $this->underlying[$this->websiteID][self::JSON_COOKIE_VISITOR_ID_KEY] ?? null;

        if (is_null($id)) {
            $id = is_null($idGenerator) ? self::newUUID() : $idGenerator();

            $this->underlying[$this->websiteID][self::JSON_COOKIE_VISITOR_ID_KEY] = $id;
        }

        return $id;
    }

    /**
     * Returns a new v4 UUID.
     *
     * @throws \Exception if random number generation fails
     */
    static function newUUID(): string
    {
        $buf = random_bytes(16);

        // this is a version 4 UUID
        $buf[6] = chr(ord($buf[6]) & 0x0f | 0x40);

        // ...of the "Leach–Salz" variant
        $buf[8] = chr(ord($buf[8]) & 0x3f | 0x80);

        return sprintf('%s%s-%s-%s-%s-%s%s%s', ...str_split(bin2hex($buf), 4));
    }

}
