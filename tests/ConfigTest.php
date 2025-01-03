<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;
use SymplifyConversion\SSTSDK\Config\SymplifyConfig;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;

// The config JSON is minimized, formatted here for readability

const CONFIG_JSON_DISCOUNT = '
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
                    "weight": 10,
                    "distribution": 10
                },
                {
                    "id": 1337,
                    "name": "huge",
                    "weight": 2,
                    "distribution": 2
                },
                {
                    "id": 9999,
                    "name": "small",
                    "weight": 1,
                    "distribution": 1
                }
            ]
        }
    ]
}
';

const CONFIG_JSON_WITH_FLOAT_WEIGHTS = '
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
                    "weight": 10.99,
                    "distribution": 10.99
                },
                {
                    "id": 1337,
                    "name": "huge",
                    "weight": 2.9,
                    "distribution": 2.9
                },
                {
                    "id": 9999,
                    "name": "small",
                    "weight": 1.9,
                    "distribution": 1.9
                }
            ]
        }
    ]
}
';

const CONFIG_JSON_WITH_BOM = "\xEF\xBB\xBF" . '
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
                    "weight": 10,
                    "distribution": 10
                },
                {
                    "id": 1337,
                    "name": "huge",
                    "weight": 2,
                    "distribution": 2
                },
                {
                    "id": 9999,
                    "name": "small",
                    "weight": 1,
                    "distribution": 1
                }
            ]
        }
    ]
}
';

const CONFIG_JSON_MISSING_ROOT_PROPERTY = '
{
    "projects": [
        {
            "id": 4711,
            "name": "discount",
            "variations": [
                {
                    "id": 42,
                    "name": "original",
                    "weight": 10,
                    "distribution": 10
                },
                {
                    "id": 1337,
                    "name": "huge",
                    "weight": 2,
                    "distribution": 2
                },
                {
                    "id": 9999,
                    "name": "small",
                    "weight": 1,
                    "distribution": 1
                }
            ]
        }
    ]
}
';

const CONFIG_JSON_MISSING_PROJECT_PROPERTY = '
{
    "updated": 1648466732,
    "projects": [
        {
            "id": 4711,
            "variations": [
                {
                    "id": 42,
                    "name": "original",
                    "weight": 10,
                    "distribution": 10
                },
                {
                    "id": 1337,
                    "name": "huge",
                    "weight": 2,
                    "distribution": 2
                },
                {
                    "id": 9999,
                    "name": "small",
                    "weight": 1,
                    "distribution": 1
                }
            ]
        }
    ]
}
';

const CONFIG_JSON_MISSING_VARIATION_PROPERTY = '
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
                    "weight": 10,
                    "distribution": 10
                },
                {
                    "id": 1337,
                    "name": "huge",
                    "weight": 2,
                    "distribution": 2
                },
                {
                    "id": 9999,
                    "name": "small"
                }
            ]
        }
    ]
}
';


final class ConfigTest extends TestCase
{
    public function testCanBeCreatedFromValidJSON(): void
    {
        $testConfig = SymplifyConfig::fromJSON(CONFIG_JSON_DISCOUNT);
        assertEquals(1648466732, $testConfig->updated);

        $testProject = $testConfig->projects[0];
        assertEquals(4711, $testProject->id);
        assertEquals('discount', $testProject->name);

        $testVariationOriginal = $testProject->variations[0];
        assertEquals(42, $testVariationOriginal->id);
        assertEquals('original', $testVariationOriginal->name);
        assertEquals(10, $testVariationOriginal->weight);

        $testVariationHuge = $testProject->variations[1];
        assertEquals(1337, $testVariationHuge->id);
        assertEquals('huge', $testVariationHuge->name);
        assertEquals(2, $testVariationHuge->weight);

        $testVariationSmall = $testProject->variations[2];
        assertEquals(9999, $testVariationSmall->id);
        assertEquals('small', $testVariationSmall->name);
        assertEquals(1, $testVariationSmall->weight);
    }

    // this is deprecated as we will phase out weight and use distribution values instead
    // I updated it to check distribution as well
    public function testFloatWeightsAreFlooredForWeightNotDistribution(): void
    {
        $testConfig = SymplifyConfig::fromJSON(CONFIG_JSON_WITH_FLOAT_WEIGHTS);
        assertEquals(1648466732, $testConfig->updated);

        $testProject = $testConfig->projects[0];
        assertEquals(4711, $testProject->id);
        assertEquals('discount', $testProject->name);

        $testVariationOriginal = $testProject->variations[0];
        assertEquals(42, $testVariationOriginal->id);
        assertEquals('original', $testVariationOriginal->name);
        assertEquals(10, $testVariationOriginal->weight);
        assertEquals(10.99, $testVariationOriginal->distribution);

        $testVariationHuge = $testProject->variations[1];
        assertEquals(1337, $testVariationHuge->id);
        assertEquals('huge', $testVariationHuge->name);
        assertEquals(2, $testVariationHuge->weight);
        assertEquals(2.9, $testVariationHuge->distribution);

        $testVariationSmall = $testProject->variations[2];
        assertEquals(9999, $testVariationSmall->id);
        assertEquals('small', $testVariationSmall->name);
        assertEquals(1, $testVariationSmall->weight);
        assertEquals(1.9, $testVariationSmall->distribution);
    }

    public function testCanBeCreatedFromValidJSONWithBOM(): void
    {
        $testConfig = SymplifyConfig::fromJSON(CONFIG_JSON_WITH_BOM);
        assertEquals(1648466732, $testConfig->updated);
        assertEquals(1, count($testConfig->projects));
        assertEquals(3, count($testConfig->projects[0]->variations));
    }

    public function testCannotBeCreatedFromInvalidJSON(): void
    {
        $testConfig = SymplifyConfig::fromJSON('invalid');
        assertEmpty($testConfig);
    }

    public function testCanBeCreatedFromJSONMissingRootProperties(): void
    {
        $testConfig = SymplifyConfig::fromJSON(CONFIG_JSON_MISSING_ROOT_PROPERTY);
        assertEquals(0, $testConfig->updated);
    }

    public function testCanBeCreatedFromJSONMissingProjectProperties(): void
    {
        $testConfig = SymplifyConfig::fromJSON(CONFIG_JSON_MISSING_PROJECT_PROPERTY);
        assertEquals('', $testConfig->projects[0]->name);
    }

    public function testCanBeCreatedFromJSONMissingVariationProperties(): void
    {
        $testConfig = SymplifyConfig::fromJSON(CONFIG_JSON_MISSING_VARIATION_PROPERTY);
        assertEquals(1, $testConfig->projects[0]->variations[2]->weight);
    }

    public function testCanBeCached(): void
    {
        $testConfig = SymplifyConfig::fromJSON(CONFIG_JSON_DISCOUNT);
        assertEquals($testConfig, unserialize(serialize($testConfig)));
    }
}
