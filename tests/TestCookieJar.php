<?php

use SymplifyConversion\SSTSDK\Cookies\CookieJar;

final class TestCookieJar implements CookieJar
{
    /** @var array<string> in-memory array for cookie testing */
    var array $cookies = [];

    public function getCookie(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    public function setCookie(string $name, string $value, int $expireInDays = 90): void //phpcs:ignore
    {
        $this->cookies[$name] = $value;
    }
}
