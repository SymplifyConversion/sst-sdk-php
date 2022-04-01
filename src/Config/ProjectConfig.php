<?php

declare(strict_types = 1);

namespace Symplify\SSTSDK\Config;

final class ProjectConfig
{

    public int $id;

    public string $name;

    /** @var array<VariationConfig> */
    public array $variations;

    /**
     * @param array<VariationConfig> $variations
     */
    function __construct(int $id, string $name, array $variations)
    {
        $this->id = $id;
        $this->name = $name;
        $this->variations = $variations;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): ProjectConfig {

        $id = $data['id'] ?? 0;
        $name = $data['name'] ?? '';

        /** @var array<VariationConfig> $variations */
        $variations = [];

        foreach($data['variations'] ?? [] as $variationData) {
            $variations[] = VariationConfig::fromArray($variationData);
        }

        return new ProjectConfig($id, $name, $variations);
    }

}
