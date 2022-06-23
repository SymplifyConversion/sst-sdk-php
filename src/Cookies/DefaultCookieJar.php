<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK\Cookies;

final class DefaultCookieJar implements CookieJar
{

    /**
     * Get the HTTP cookie from the current request with the given name.
     */
    public function getCookie(string $name): ?string
    {
        // phpcs:ignore
        return $_COOKIE[$name] ?? null;
    }

    /**
     * Set the HTTP cookie for the current response, with the given name, the given value.
     */
    public function setCookie(string $name, string $value): void
    {
        setcookie($name, $value);
    }

}
