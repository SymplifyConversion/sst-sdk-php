<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK;

use SymplifyConversion\SSTSDK\Config\ProjectConfig;
use SymplifyConversion\SSTSDK\Config\RunState;
use SymplifyConversion\SSTSDK\Config\VariationConfig;

final class Allocation
{

    /**
     * Get the variation in the given project that the given visitor ID is allocated for.
     * The allocation is the same given the same project ID, visitor ID, variation IDs, variation order,
     * and variation weights, but it appears random given randomly distributed visitor IDs.
     *
     * If the visitor ID is empty, always returns the original variation.
     *
     * @return ?VariationConfig the allocated variation, or null
     */
    public static function findVariationForVisitor(ProjectConfig $project, string $visitorID): ?VariationConfig
    {
        if ('' === $visitorID || RunState::ACTIVE !== $project->state) {
            return null;
        }

        $hashKey = "$visitorID:$project->id";
        $hash    = Hash::hash_in_window($hashKey, 100);

        $pointer = 0;

        foreach ($project->variations as $variationConfig) {
            $pointer += ($variationConfig->distribution > 0) 
            ? $variationConfig->distribution : $variationConfig->weight;

            if ($hash <= $pointer) {
                return RunState::ACTIVE === $variationConfig->state ? $variationConfig : null;
            }
        }

        return null;
    }

}
