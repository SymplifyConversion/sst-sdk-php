<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK\Config;

final class VariationConfig
{

    public int $id;

    public string $name;

    /** @var int relative variation weight */
    public int $weight;

    public int $state;

    function __construct(int $id, string $name, int $state, int $weight)
    {
        $this->id     = $id;
        $this->name   = $name;
        $this->state  = $state;
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
        $state  = RunState::fromString($data['state'] ?? null);

        return new VariationConfig($id, $name, $state, (int)$weight);
    }

}
