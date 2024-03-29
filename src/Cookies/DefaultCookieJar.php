<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK\Cookies;

final class DefaultCookieJar implements CookieJar
{

    private ?string $cookieDomain;

    /**
     * @param string|null $cookieDomain set this if you need to write Symplify cookies
     * on some other domain than what PHP defaults to.
     */
    public function __construct(?string $cookieDomain = null)
    {
        $this->cookieDomain = $cookieDomain;
    }

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
    public function setCookie(string $name, string $value, int $expireInDays): void
    {
        $expireTime = time() + $expireInDays * 60 * 60 * 24;

        setcookie($name, $value, $expireTime, '/', $this->cookieDomain ?? '', true);
    }

}
