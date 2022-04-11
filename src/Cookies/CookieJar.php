<?php

declare(strict_types=1);

namespace Symplify\SSTSDK\Cookies;

interface CookieJar
{

    /**
     * Get the HTTP cookie from the current request with the given name.
     */
    public function getCookie(string $name): string;

    /**
     * Set the HTTP cookie for the current response, with the given name, the given value.
     */
    public function setCookie(string $name, string $value): void;

}
