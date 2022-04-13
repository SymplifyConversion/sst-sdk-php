<?php

declare(strict_types=1);

namespace Symplify\SSTSDK\Config;

final class VariationConfig
{

    public int $id;

    public string $name;

    /** @var int relative variation weight */
    public int $weight;

    function __construct(int $id, string $name, int $weight)
    {
        $this->id     = $id;
        $this->name   = $name;
        $this->weight = $weight;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): VariationConfig
    {

        $id     = $data['id'] ?? 0;
        $name   = $data['name'] ?? '';
        $weight = $data['weight'] ?? 1;

        return new VariationConfig($id, $name, (int)$weight);
    }

}
