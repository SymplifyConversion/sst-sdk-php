<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Symplify\SSTSDK\Cookies\CookieJar;
use Symplify\SSTSDK\Visitor;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertMatchesRegularExpression;
use function PHPUnit\Framework\assertNotEquals;

final class VisitorTest extends TestCase
{

    public function testSetCookie(): void
    {
        $cookies = newCookieJar();
        $returnedID = Visitor::ensureVisitorID($cookies, generateConstantID('goober'));
        assertEquals('goober', $returnedID);
        assertEquals('goober', $cookies->getCookie('sg_sst_vid'));
    }

    public function testReuseCookie(): void
    {
        $cookies = newCookieJar();
        $returnedIDA = Visitor::ensureVisitorID($cookies, generateConstantID('goober'));
        $returnedIDB = Visitor::ensureVisitorID($cookies, generateConstantID('flubber'));
        assertEquals('goober', $returnedIDA);
        assertEquals('goober', $returnedIDB);
    }

    public function testGenerateUUID(): void
    {
        $returnedIDA = Visitor::ensureVisitorID(newCookieJar());
        $returnedIDB = Visitor::ensureVisitorID(newCookieJar());
        $uuidPattern = '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/';
        assertMatchesRegularExpression($uuidPattern, $returnedIDA);
        assertMatchesRegularExpression($uuidPattern, $returnedIDB);
        assertNotEquals($returnedIDA, $returnedIDB);
    }

}

function newCookieJar(): CookieJar
{
    return new class implements CookieJar {

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

    };
}

function generateConstantID(string $id): callable
{
    return static function () use ($id) {
        return $id;
    };
}
