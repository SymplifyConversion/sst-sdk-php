<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SymplifyConversion\SSTSDK\Visitor;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertMatchesRegularExpression;
use function PHPUnit\Framework\assertNotEquals;

include_once "TestCookieJar.php";

final class VisitorTest extends TestCase
{

    private const JSON_COOKIE_NAME = 'sg_cookies';
    private const VISITOR_ID_COOKIE_KEY = 'visid';

    public function testSetJsonCookie(): void
    {
        $logger        = new NullLogger();
        $cookies       = new TestCookieJar();
        $testWebsiteID = '4711';

        $returnedID = Visitor::ensureVisitorID($cookies, $logger, $testWebsiteID, generateConstantID('goober'));
        $cookieObj  = json_decode($cookies->getCookie(self::JSON_COOKIE_NAME), true);

        assertEquals('goober', $returnedID);
        assertEquals('goober', $cookieObj[$testWebsiteID][self::VISITOR_ID_COOKIE_KEY]);
    }

    public function testReuseJsonCookie(): void
    {
        $logger  = new NullLogger();
        $cookies = new TestCookieJar();

        // phpcs:ignore
        $rawCookie  = '{%2210001%22:{%22100000002%22:[300001]%2C%22100000001%22:[300002]%2C%22100000002_ch%22:1%2C%22100000001_ch%22:1%2C%22lv%22:1650967549303%2C%22rf%22:%22%22%2C%22pv%22:2%2C%22pv_p%22:{%22100000002%22:2%2C%22100000001%22:2}%2C%22tv%22:2%2C%22tv_p%22:{%22100000002%22:2%2C%22100000001%22:2}%2C%22aud_p%22:[100000002%2C100000001]%2C%22visid%22:%2278ac2972-de5f-4262-bfdb-7296eb132a94%22%2C%22commid%22:%221be9f08d-c36c-4bce-b157-e057e050027c%22}%2C%22_g%22:1}';
        $cookieJSON = urldecode($rawCookie); // PHP does this automatically for $_COOKIE
        $cookies->setCookie(self::JSON_COOKIE_NAME, $cookieJSON);

        $returnedID = Visitor::ensureVisitorID($cookies, $logger, '10001', generateConstantID('goober'));
        assertEquals('78ac2972-de5f-4262-bfdb-7296eb132a94', $returnedID);
    }

    public function testAbortOnUnknownJsonCookieVersion(): void
    {
        $logger  = new NullLogger();
        $cookies = new TestCookieJar();

        // phpcs:ignore
        $rawCookie  = '{%2210001%22:{%22100000002%22:[300001]%2C%22100000001%22:[300002]%2C%22100000002_ch%22:1%2C%22100000001_ch%22:1%2C%22lv%22:1650967549303%2C%22rf%22:%22%22%2C%22pv%22:2%2C%22pv_p%22:{%22100000002%22:2%2C%22100000001%22:2}%2C%22tv%22:2%2C%22tv_p%22:{%22100000002%22:2%2C%22100000001%22:2}%2C%22aud_p%22:[100000002%2C100000001]%2C%22visid%22:%2278ac2972-de5f-4262-bfdb-7296eb132a94%22%2C%22commid%22:%221be9f08d-c36c-4bce-b157-e057e050027c%22}%2C%22_g%22:1000000}';
        $cookieJSON = urldecode($rawCookie); // PHP does this automatically for $_COOKIE
        $cookies->setCookie(self::JSON_COOKIE_NAME, $cookieJSON);

        $returnedID = Visitor::ensureVisitorID($cookies, $logger, '42', generateConstantID('goober'));
        assertEquals('', $returnedID);
    }

    public function testGenerateUUID(): void
    {
        $logger      = new NullLogger();
        $returnedIDA = Visitor::ensureVisitorID(new TestCookieJar(), $logger, "doesn't matter");
        $returnedIDB = Visitor::ensureVisitorID(new TestCookieJar(), $logger, "don't care");
        $uuidPattern = '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/';
        assertMatchesRegularExpression($uuidPattern, $returnedIDA);
        assertMatchesRegularExpression($uuidPattern, $returnedIDB);
        assertNotEquals($returnedIDA, $returnedIDB);
    }

}

function generateConstantID(string $id): callable
{
    return static function () use ($id) {
        return $id;
    };
}
