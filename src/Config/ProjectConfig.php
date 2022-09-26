<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK\Config;

final class ProjectConfig
{

    public int $id;

    public string $name;

    /** @var array<VariationConfig> */
    public array $variations;

    public int $state;

    /** @var ?mixed[] */
    public ?array $audience_rules;

    /**
     * @param array<VariationConfig> $variations
     */
    function __construct(int $id, string $name, int $state, array $variations, ?array $audience_rules = null)
    {
        $this->id         = $id;
        $this->name       = $name;
        $this->state      = $state;
        $this->variations = $variations;
        $this->audience_rules = $audience_rules;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): ProjectConfig
    {

        $id    = $data['id'] ?? 0;
        $name  = $data['name'] ?? '';
        $state = RunState::fromString($data['state'] ?? null);

        /** @var array<VariationConfig> $variations */
        $variations = [];

        foreach ($data['variations'] ?? [] as $variationData) {
            $variations[] = VariationConfig::fromArray($variationData);
        }

        $audience_rules = $data['audience_rules'] ?? null;

        return new ProjectConfig($id, $name, $state, $variations, $audience_rules);
    }

    function findVariationWithID(int $variationID): ?VariationConfig
    {
        foreach ($this->variations as $variation) {
            if ($variation->id === $variationID) {
                return $variation;
            }
        }

        return null;
    }

    function findVariationWithName(string $variationName): ?VariationConfig
    {
        foreach ($this->variations as $variation) {
            if ($variation->name === $variationName) {
                return $variation;
            }
        }

        return null;
    }

}
