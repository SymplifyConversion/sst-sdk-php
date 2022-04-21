<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SymplifyConversion\SSTSDK\Allocation;
use SymplifyConversion\SSTSDK\Config\ProjectConfig;
use SymplifyConversion\SSTSDK\Config\SymplifyConfig;
use SymplifyConversion\SSTSDK\Config\VariationConfig;
use function PHPUnit\Framework\assertEquals;

const ALLOCATION_TEST_PROJECT_JSON = '
{
    "updated": 1648466732,
    "projects": [
        {
            "id": 4711,
            "name": "discount",
            "variations": [
                {
                    "id": 42,
                    "name": "original",
                    "weight": 2
                },
                {
                    "id": 1337,
                    "name": "massive",
                    "weight": 1
                }
            ]
        }
    ]
}
';

/**
 * This test suite is the same across the SDK implementations for all platforms.
 */
final class AllocationTest extends TestCase
{
    var ProjectConfig $testProject;

    // @phpstan-ignore-next-line
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->testProject = SymplifyConfig::fromJson(ALLOCATION_TEST_PROJECT_JSON)->projects[0];
    }

    public function testAllocateIsWeighted(): void
    {
        $variation = Allocation::findVariationForVisitor($this->testProject, "foobar");
        assertEquals(42, $variation->id);
    }

    public function testAllocateIsDistributed(): void
    {
        $variation = Allocation::findVariationForVisitor($this->testProject, "Fabian");
        assertEquals(1337, $variation->id);
    }

    public function testAllocateEmptyVisitorID(): void
    {
        $originalID = random_int(1, 1000);
        $variationID = random_int(1001, 2000);
        $testProject = new ProjectConfig(
            10000,
            'test project',
            [
                new VariationConfig(
                    $originalID,
                    'Original',
                    1,
                ),
                new VariationConfig(
                    $variationID,
                    'Variation',
                    1000,
                ),
            ]
        );
        $variation = Allocation::findVariationForVisitor($testProject, "");
        assertEquals($originalID, $variation->id);
    }

    public function testAllocateLongVisitorID(): void
    {
        $visid = "1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890";
        $variation = Allocation::findVariationForVisitor($this->testProject, $visid);
        assertEquals(1337, $variation->id);
    }

}
