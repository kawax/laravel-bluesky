<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Core\CBOR;

/**
 * CBOR requires an array to be sorted in a specific order.
 *
 * @internal
 */
final class MapKeySort
{
    public function __invoke(string $a, string $b): int
    {
        if (strlen($a) < strlen($b)) {
            return -1;
        } elseif (strlen($b) < strlen($a)) {
            return 1;
        } else {
            return ($a < $b) ? -1 : 1;
        }
    }
}
