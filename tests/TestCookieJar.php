<?php

use SymplifyConversion\SSTSDK\Cookies\CookieJar;

final class TestCookieJar implements CookieJar
{
    /** @var array<string> in-memory array for cookie testing */
    var array $cookies = [];

    public function getCookie(string $name): string
    {
        return $this->cookies[$name] ?? '';
    }

    public function setCookie(string $name, string $value): void
    {
        $this->cookies[$name] = $value;
    }
}
