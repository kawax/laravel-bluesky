<?php

namespace Revolution\Bluesky\Crypto\Format;

use Illuminate\Support\Str;
use phpseclib3\Crypt\EC\BaseCurves\Base;
use phpseclib3\Math\PrimeField\Integer;

/**
 * phpseclib custom key format.
 */
final class Compress
{
    /**
     * @param  array<Integer, Integer>  $publicKey
     */
    public static function savePublicKey(Base $curve, array $publicKey, array $options = []): string
    {
        $prefix = $publicKey[1]->isOdd() ? '03' : '02';

        // 32
        $length = $curve->getLengthInBytes();

        $hexString = Str::padLeft($publicKey[0]->toHex(), $length, pad: '0');

        return $prefix.$hexString;
    }
}
