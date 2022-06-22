<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK;

use Psr\Log\LoggerInterface;
use SymplifyConversion\SSTSDK\Cookies\CookieJar;

final class Visitor
{

    private const JSON_COOKIE_NAME = 'sg_cookies';
    private const JSON_COOKIE_VERSION_KEY = '_g';
    private const JSON_COOKIE_VISITOR_ID_KEY = 'visid';
    private const SUPPORTED_JSON_COOKIE_VERSION = 1;

    /**
     * Gets the visitor ID from our cookie, if uninitialized, create one and update the cookie.
     * This keeps visitor IDs in sync with frontend logic.
     *
     * @param CookieJar       $cookies where to get and set visitor ID cookies
     * @param LoggerInterface $logger used for reporting ID generation errors
     * @param string          $websiteID used for managing the visitor cookie
     * @param ?Callable       $makeID called to generate new random IDs, if null, a v4 UUID will be generated
     * @returns string the assigned visitor ID, empty string if ID generation failed
     */
    public static function ensureVisitorID(
        CookieJar $cookies,
        LoggerInterface $logger,
        string $websiteID,
        ?callable $makeID = null
    ): string
    {
        $cookieJSON = $cookies->getCookie(self::JSON_COOKIE_NAME);

        if (is_null($cookieJSON)) {
            $sgCookies = [
                self::JSON_COOKIE_VERSION_KEY => self::SUPPORTED_JSON_COOKIE_VERSION,
            ];
        } else {
            $sgCookies = json_decode($cookieJSON, true);

            if (is_null($sgCookies)) {
                $info = json_last_error_msg();
                $logger->error("cannot parse JSON cookie: $info");

                return '';
            }
        }

        $cookieGeneration = $sgCookies[self::JSON_COOKIE_VERSION_KEY] ?? null;

        if (self::SUPPORTED_JSON_COOKIE_VERSION !== $cookieGeneration) {
            $logger->error(sprintf(
                "ID detection failed: unknown generation '%d' in cookie '%s'",
                $cookieGeneration ?? 'null',
                self::JSON_COOKIE_NAME
            ));

            return '';
        }

        $visitorID = $sgCookies[$websiteID][self::JSON_COOKIE_VISITOR_ID_KEY] ?? null;

        if (!$visitorID) {
            try {
                $visitorID = $makeID ? $makeID() : self::newUUID();
            } catch (\Throwable $t) {
                $logger->error('ID generation failed', ['exception' => $t->getMessage()]);

                // return early to avoid setting the cookie
                return '';
            }

            $sgCookies[$websiteID][self::JSON_COOKIE_VISITOR_ID_KEY] = $visitorID;

            $cookieJSON = json_encode($sgCookies);
            $cookies->setCookie(self::JSON_COOKIE_NAME, $cookieJSON);
        }

        return $visitorID;
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
