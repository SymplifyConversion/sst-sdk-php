<?php
declare(strict_types = 1);

namespace Symplify\SSTSDK\Config;

class ProjectConfig
{
    /** @var int */
    public $id;
    /** @var string */
    public $name;
    /** @var VariationConfig[] */
    public $variations;

    /**
     * @param int $id
     * @param string $name
     * @param VariationConfig[] $variations
     */
    function __construct($id, $name, $variations)
    {
        $this->id = $id;
        $this->name = $name;
        $this->variations = $variations;
    }

    /**
     * @param mixed $data
     */
    public static function fromArray($data): ProjectConfig {

        $id = $data['id'] ?? 0;
        $name = $data['name'] ?? '';

        /** @var VariationConfig[] */
        $variations = [];
        foreach($data['variations'] ?? [] as $variationData) {
            $variations[] = VariationConfig::fromArray($variationData);
        }

        return new ProjectConfig($id, $name, $variations);
    }
}
