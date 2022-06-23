<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK\Config;

final class RunState
{

    public const PAUSED = 0;
    public const ACTIVE = 1;

    public static function fromString(?string $str): int
    {
        switch ($str) {
            case "active":
                return self::ACTIVE;

            case "paused":
            default:
                return self::PAUSED;
        }
    }

}
