<?php

declare(strict_types=1);

namespace Symplify\SSTSDK;

use Symplify\SSTSDK\Config\ProjectConfig;
use Symplify\SSTSDK\Config\VariationConfig;

final class Allocation
{

    /**
     * Get the variation in the given project that the given visitor ID is allocated for.
     * The allocation is the same given the same project ID, visitor ID, variation IDs, variation order,
     * and variation weights, but it appears random given randomly distributed visitor IDs.
     *
     * If the visitor ID is empty, always returns the original variation.
     *
     * @return VariationConfig the allocated variation
     * @throws \Exception if the variation weight configuration in $project is invalid
     */
    public static function findVariationForVisitor(ProjectConfig $project, string $visitorID): VariationConfig
    {
        if ('' === $visitorID) {
            return $project->findVariationWithName('Original') ?: self::lookupVariationAt($project, 1);
        }

        $allocation = self::getAllocation($project, $visitorID);

        return self::lookupVariationAt($project, $allocation);
    }

    /**
     * Get a "weighted" pointer into the variations of $project based on a hashed visitor ID.
     */
    private static function getAllocation(ProjectConfig $project, string $visitorID): int
    {
        $hashKey     = "$visitorID:$project->id";
        $totalWeight = 0;

        foreach ($project->variations as $variation) {
            $totalWeight += $variation->weight;
        }

        return Hash::hash_in_window($hashKey, $totalWeight);
    }

    /**
     * Find the variation matching $allocation in $project, by comparing allocation to the relative weight.
     *
     * @throws \Exception if $allocation is outside the weight span of the variations in $project
     */
    private static function lookupVariationAt(ProjectConfig $project, int $allocation): VariationConfig
    {

        $totalWeight         = 0;
        $variationThresholds = [];

        foreach ($project->variations as $variationConfig) {
            $totalWeight           += $variationConfig->weight;
            $variationThresholds[] = [$totalWeight, $variationConfig->id];
        }

        foreach ($variationThresholds as $variationThreshold) {
            $threshold   = $variationThreshold[0];
            $variationID = $variationThreshold[1];

            if ($allocation <= $threshold) {
                $allocatedVariation = $project->findVariationWithID($variationID);

                break;
            }
        }

        if (!isset($allocatedVariation)) {
            throw new \Exception("[SSTSDK] cannot allocate variation with $allocation in $totalWeight");
        }

        return $allocatedVariation;
    }

}
