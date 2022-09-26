<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SymplifyConversion\SSTSDK\Audience\SymplifyAudience;
use SymplifyConversion\SSTSDK\Config\ClientConfig;

/**
 * This test suite is the same across the SDK implementations for all platforms.
 */
final class AudienceTest extends TestCase
{
    public function audienceTestAttributesProvider(): array {
        $json      = file_get_contents(__DIR__ . "/data/audience_attributes_spec.json");
        $attributesData = json_decode($json, true);

        $attributes = array();

        foreach ($attributesData as $attributeData) {
            $attributes[$attributeData['suite_name']] = [
                json_encode($attributeData['audience_json']) ?? null,
                $attributeData['test_cases'] ?? array(),
            ];
        }

        return $attributes;
    }

    public function audienceTestProvider(): array {
        $json      = file_get_contents(__DIR__ . "/data/audience_spec.json");
        $audiencesData = json_decode($json, true);
        $test_cases = array();
        foreach($audiencesData as $audienceData){
            foreach ($audienceData['test_cases'] as $test_case) {
                $test_cases[$audienceData['suite_name']] = [
                    json_encode($test_case['audience_json']) ?? null,
                    $test_case['expect_result'] ?? $test_case['expect_error'],
                ];
            }
        }

        return $test_cases;
    }

    public function audienceTestTracingProvider(): array {
        $json      = file_get_contents(__DIR__ . "/data/audience_tracing_spec.json");
        $tracesData = json_decode($json, true);

        $test_cases = array();

            foreach ($tracesData as $traceData) {
                $test_cases[$traceData['test_name']] = [
                    json_encode($traceData['rules']) ?? null,
                    $traceData ?? array(),
                    $traceData['expect_trace'] ?? null,
                ];
            }

        return $test_cases;
    }

    public function audienceTestValidationProvider(): array {
        $json      = file_get_contents(__DIR__ . "/data/audience_validation_spec.json");
        $validationsData = json_decode($json, true);

        $test_cases = array();

        foreach ($validationsData as $validationData) {
            $test_cases[$validationData['suite_name']] = [
                $validationData['test_cases'] ?? array()
            ];
        }

        return $test_cases;
    }

    /**
     * @dataProvider audienceTestAttributesProvider
     */
    public function testAudienceAttributes( string $audience_json, array $test_cases): void
    {
        $cfg = (new ClientConfig('4711'));
        $audience = new SymplifyAudience($audience_json, $cfg->getLogger());
        foreach($test_cases as $test_case){
            $expectation = $test_case['expect_result'] ?? $test_case['expect_error'];
            $actual_result = $audience->eval($test_case['attributes']);

            self::assertEquals( $expectation, $actual_result );
        }

    }

    /**
     * @dataProvider audienceTestProvider
     */
    public function testAudiences( string $audience_json, $expectation): void
    {
        $cfg = (new ClientConfig('4711'));
        $audience = new SymplifyAudience($audience_json, $cfg->getLogger());

        $actual_result = $audience->eval();

        self::assertEquals( $expectation, $actual_result );

    }

    /**
     * @dataProvider audienceTestTracingProvider
     */
    public function testAudienceTracing( string $audience_json, $environment, array $expectation): void
    {
        $cfg = (new ClientConfig('4711'));
        $audience = new SymplifyAudience($audience_json, $cfg->getLogger());

        $actual_result = $audience->trace($environment['attributes']);

        self::assertEquals( $expectation, $actual_result );

    }

    /**
     * @dataProvider audienceTestValidationProvider
     */
    public function testAudienceValidation(array $test_cases): void
    {
        $cfg = (new ClientConfig('4711'));
        foreach($test_cases as $test_case){
            $expectation = $test_case['expect_error'];
            $audience = new SymplifyAudience($test_case['audience_string'], $cfg->getLogger());
            self::assertStringContainsString( $expectation, $audience->eval() );
        }
    }


}
