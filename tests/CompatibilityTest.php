<?php

declare(strict_types=1);

use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use SymplifyConversion\SSTSDK\Client;
use SymplifyConversion\SSTSDK\Config\ClientConfig;
use SymplifyConversion\SSTSDK\ErrorLogLogger;

include_once "TestCookieJar.php";

final class CompatibilityTest extends TestCase
{

    // @phpstan-ignore-next-line
    public function compatibilityTestProvider(): array // phpcs:ignore
    {
        $json      = file_get_contents("https://raw.githubusercontent.com/SymplifyConversion/sst-documentation/main/test/test_cases.json");
        $casesData = json_decode($json, true);

        $cases = array();

        foreach ($casesData as $caseData) {
            $cases[$caseData['test_name']] = [
                $caseData['skip'] ?? null,

                // test data
                $caseData['sdk_config'],
                $caseData['website_id'],
                $caseData['cookies'] ?? array(),
                $caseData['test_project_name'],
                $caseData['audience_attributes'] ?? [],

                // expected results
                $caseData['expect_variation_match'] ?? null,
                $caseData['expect_sg_cookie_properties_match'] ?? array(),
                $caseData['expect_extra_cookies'] ?? array(),
            ];
        }

        return $cases;
    }

    /**
     * @dataProvider compatibilityTestProvider
     * @param array<string, string> $cookies
     * @param array<mixed>          $audience_attributes
     * @param array<mixed>          $expect_sg_cookie_properties_match
     * @param array<string, string> $expect_extra_cookies
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function testCompatibilityCase(
        ?string $skip,
        string $sdk_config,
        string $website_id,
        array $cookies,
        string $test_project_name,
        array $audience_attributes,
        ?string $expect_variation_match,
        array $expect_sg_cookie_properties_match,
        array $expect_extra_cookies
    ): void
    {
        if ($skip) {
            self::markTestSkipped($skip);
        }

        $messageFactory = new Psr17Factory();
        $httpClient     = new MockClient();

        $configJSON   = file_get_contents("https://raw.githubusercontent.com/SymplifyConversion/sst-documentation/main/test/" . $sdk_config);
        $jsonResponse = $messageFactory->createResponse(200)->withBody(Stream::create($configJSON));
        $httpClient->setDefaultResponse($jsonResponse);

        $clientConfig = (new ClientConfig($website_id))
            // this can help when debugging locally ->withLogger(new ErrorLogLogger())
            ->withCdnBaseURL("http://unittest.example.com")
            ->withHttpClient($httpClient)
            ->withHttpRequests($messageFactory);

        $sdk = new Client($clientConfig);
        $sdk->loadConfig();

        $cookieJar = new TestCookieJar();

        foreach ($cookies as $cookieName => $cookieValue) {
            $cookieJar->setCookie($cookieName, urldecode($cookieValue));
        }

        $gotVariation = $sdk->findVariation($test_project_name, $audience_attributes, $cookieJar);

        if (null === $expect_variation_match || null === $gotVariation) {
            self::assertEquals($expect_variation_match, $gotVariation);
        } else {
            self::assertMatchesRegularExpression("/$expect_variation_match/", $gotVariation);
        }

        $sgCookiesAfter = json_decode($cookieJar->getCookie('sg_cookies') ?? '{}', true);

        foreach ($expect_sg_cookie_properties_match as $propertyKey => $expectProperty) {
            $parts = preg_split("_/_", "$propertyKey");
            $prop  = $sgCookiesAfter;

            do {
                $key = array_shift($parts);
                $prop = $prop[$key] ?? (0 < count($parts) ? [] : null);
            } while (0 < count($parts));

            if ("string" === gettype($expectProperty)) {
                self::assertMatchesRegularExpression("_${expectProperty}_", $prop);
            } else {
                self::assertEquals($expectProperty, $prop);
            }
        }

        foreach ($expect_extra_cookies as $cookieName => $expectCookieValue) {
            $actualCookieValue = $cookieJar->getCookie($cookieName) ?? null;
            self::assertEquals($expectCookieValue, $actualCookieValue);
        }
    }

}
