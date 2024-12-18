<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Crypto;

use phpseclib3\Crypt\EC\Formats\Signature\ASN1;
use phpseclib3\Math\BigInteger;

final class Signature
{
    /**
     * Recovery compact sig.
     *
     * @param  string  $sig  signature bytes
     */
    public static function fromCompact(string $sig): string
    {
        if (strlen($sig) > 64) {
            return $sig;
        }

        $r = substr($sig, 0, 32);
        $s = substr($sig, 32, 32);

        $r = new BigInteger($r, 256);
        $s = new BigInteger($s, 256);

        return ASN1::save($r, $s);
    }
}
