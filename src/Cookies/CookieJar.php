<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK\Cookies;

interface CookieJar
{

    /**
     * Get the HTTP cookie from the current request with the given name.
     * The result should be URL decoded.
     */
    public function getCookie(string $name): ?string;

    /**
     * Set the HTTP cookie for the current response, with the given name, the given value.
     * The expires field of the cookie must be `$expireInDays` days from now.
     * The domain field of the cookie should be the domain you run your test projects on.
     * The cookie value should be URL encoded.
     */
    public function setCookie(string $name, string $value, int $expireInDays): void;

}
