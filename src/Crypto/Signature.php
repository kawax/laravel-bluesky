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
        if (strlen($sig) !== 64) {
            return $sig;
        }

        [$r, $s] = str_split($sig, 32);

        $r = new BigInteger($r, 256);
        $s = new BigInteger($s, 256);

        return ASN1::save($r, $s);
    }

    /**
     * Convert signature to compact.
     */
    public static function toCompact(string $sig): string
    {
        if (strlen($sig) === 64) {
            return $sig;
        }

        $arr = ASN1::load($sig);

        /** @var BigInteger $r */
        $r = $arr['r'];
        /** @var BigInteger $s */
        $s = $arr['s'];

        $rBytes = str_pad($r->toBytes(), 32, "\0", STR_PAD_LEFT);
        $sBytes = str_pad($s->toBytes(), 32, "\0", STR_PAD_LEFT);

        return $rBytes.$sBytes;
    }
}
