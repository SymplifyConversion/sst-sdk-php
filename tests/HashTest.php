<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SymplifyConversion\SSTSDK\Hash;
use function PHPUnit\Framework\assertEquals;

// these hashing tests are the same in all SDK implementations
final class HashTest extends TestCase
{

    public function testDjb2(): void
    {
        assertEquals(5_381, Hash::djb2_xor(""), "djb2 empty string works");
        assertEquals(2_515_910_790, Hash::djb2_xor("Fabian"), "djb2 short string works");
        assertEquals(913_699_141, Hash::djb2_xor("9e66a7fa-984a-4681-9319-80c2be2ffe8a"), "djb2 UUID string 1 works");
        assertEquals(1_619_464_113, Hash::djb2_xor("72784e9c-f5ae-4aed-8ae7-baa9c6e31d3c"), "djb2 UUID string 2 works");
        assertEquals(3_367_636_261, Hash::djb2_xor("cc615f71-1ab8-4322-b7d7-e10294a8d483"), "djb2 UUID string 3 works");
    }

    public function testHashInWindow(): void
    {
        assertEquals(5.857811287478034, Hash::hash_in_window("Fabian", 10), "hashInWindow is not random");
        assertEquals(585.7811287478035, Hash::hash_in_window("Fabian", 1_000), "hashInWindow can scale up");
        assertEquals(1.1715622574956068, Hash::hash_in_window("Fabian", 2), "hashInWindow can scale down");

        assertEquals(
            0.6382114774636486,
            Hash::hash_in_window("9e66a7fa-984a-4681-9319-80c2be2ffe8a", 3),
            "hashInWindow is distributed 1",
        );
        assertEquals(
            1.1311826156757732,
            Hash::hash_in_window("72784e9c-f5ae-4aed-8ae7-baa9c6e31d3c", 3),
            "hashInWindow is distributed 2",
        );
        assertEquals(
            2.352266755269903,
            Hash::hash_in_window("cc615f71-1ab8-4322-b7d7-e10294a8d483", 3),
            "hashInWindow is distributed 3",
        );
        assertEquals(
            56.05795827136794,
            Hash::hash_in_window("b7850777-f581-4f66-ad3e-4e54963661df", 100),
            "hashInWindow is compatible",
        );
    }

}
