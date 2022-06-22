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
        $json      = file_get_contents(__DIR__ . "/data/test_cases.json");
        $casesData = json_decode($json, true);

        $cases = array();

        foreach ($casesData as $caseData) {
            $cases[$caseData['test_name']] = [
                $caseData['skip'] ?? null,
                $caseData['sdk_config'],
                $caseData['website_id'],
                $caseData['cookies'] ?? array(),
                $caseData['test_project_name'],
                $caseData['expect_variation_match'],
                $caseData['expect_sg_cookie_properties_match'],
            ];
        }

        return $cases;
    }

    /**
     * @dataProvider compatibilityTestProvider
     * @param array<string, string> $cookies
     * @param array<mixed>          $expect_sg_cookie_properties_match
     */
    public function testCompatibilityCase(
        ?string $skip,
        string $sdk_config,
        string $website_id,
        array $cookies,
        string $test_project_name,
        ?string $expect_variation_match,
        array $expect_sg_cookie_properties_match
    ): void
    {
        if ($skip) {
            self::markTestSkipped($skip);
        }

        $messageFactory = new Psr17Factory();
        $httpClient     = new MockClient();

        $configJSON   = file_get_contents(__DIR__ . "/data/$sdk_config");
        $jsonResponse = $messageFactory->createResponse(200)->withBody(Stream::create($configJSON));
        $httpClient->setDefaultResponse($jsonResponse);

        $clientConfig = (new ClientConfig($website_id))
            ->withLogger(new ErrorLogLogger())
            ->withCdnBaseURL("http://unittest.example.com")
            ->withHttpClient($httpClient)
            ->withHttpRequests($messageFactory);

        $sdk = new Client($clientConfig);
        $sdk->loadConfig();

        $cookieJar = new TestCookieJar();

        foreach ($cookies as $cookieName => $cookieValue) {
            $cookieJar->setCookie($cookieName, urldecode($cookieValue));
        }

        $gotVariation = $sdk->findVariation($test_project_name, $cookieJar);

        if (null === $expect_variation_match || null === $gotVariation) {
            self::assertEquals($expect_variation_match, $gotVariation);
        } else {
            self::assertMatchesRegularExpression("/$expect_variation_match/", $gotVariation);
        }
    }
}
