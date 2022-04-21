<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK;

use Psr\Log\LoggerInterface;
use SymplifyConversion\SSTSDK\Cookies\CookieJar;

final class Visitor
{

    /**
     * Gets the visitor ID from our cookie, if uninitialized, create one and update the cookie.
     * This keeps visitor IDs in sync with frontend logic.
     *
     * @param CookieJar       $cookies where to get and set visitor ID cookies
     * @param LoggerInterface $logger used for reporting ID generation errors
     * @param ?Callable       $makeID called to generate new random IDs, if null, a v4 UUID will be generated
     * @returns string the assigned visitor ID, empty string if ID generation failed
     */
    public static function ensureVisitorID(
        CookieJar $cookies,
        LoggerInterface $logger,
        ?callable $makeID = null
    ): string
    {
        $cookieName = 'sg_sst_vid';
        $visitorID  = $cookies->getCookie($cookieName);

        if (!$visitorID) {
            try {
                $visitorID = $makeID ? $makeID() : self::newUUID();
            } catch (\Throwable $t) {
                $logger->error('ID generation failed', ['exception' => $t->getMessage()]);

                // return to avoid setting the cookie
                return '';
            }

            $cookies->setCookie($cookieName, $visitorID);
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
