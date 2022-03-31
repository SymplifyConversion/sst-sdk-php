<?php
declare(strict_types = 1);

namespace Symplify\SSTSDK\Config;

class VariationConfig
{
    /** @var int */
    public $id;
    /** @var string */
    public $name;
    /** @var float */
    public $weight;

    /**
     * @param int $id
     * @param string $name
     * @param float $weight
     */
    function __construct($id, $name, $weight)
    {
        $this->id = $id;
        $this->name = $name;
        $this->weight = $weight;
    }

    /**
     * @param mixed $data
     */
    public static function fromArray($data): VariationConfig {

        $id = $data['id'] ?? 0;
        $name = $data['name'] ?? '';
        $weight = $data['weight'] ?? 1;

        return new VariationConfig($id, $name, $weight);
    }
}
