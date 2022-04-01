<?php

declare(strict_types=1);

namespace Symplify\SSTSDK;

final class Hash
{

    /**
     * See the docs for an explanation of the variation assignment algorithm.
     *
     * @param string $key the string to be hashed
     * @param int $window the maximum value (inclusive) to scale the hash to
     * @return int a value between 1 and $window
     */
    public static function hash_in_window(string $key, int $window): int
    {
        $unsignedMax = 4_294_967_295;

        $h = self::djb2_xor($key);

        // scale $h to the desired window
        $h /= $unsignedMax;                // scale to fit [0,1]
        $h = (int)ceil($h * $window); // scale to window

        return $h;
    }

    /**
     * The same hash function is used in the js-sdk.
     *
     * The algorithm: http://www.cse.yorku.ca/%7Eoz/hash.html
     * Why we picked it: https://softwareengineering.stackexchange.com/questions/49550/which-hashing-algorithm-is-best-for-uniqueness-and-speed
     */
    public static function djb2_xor(string $str): int
    {
        $hash   = 5_381;
        $length = strlen($str);

        for ($i = 0; $i < $length; $i+=1) {
            $c    = ord($str[$i]);
            $hash = 33 * $hash & 0xFFFFFFFF ^ $c;
        }

        return $hash;
    }

}
