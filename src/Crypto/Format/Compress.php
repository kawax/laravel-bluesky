<?php

namespace Revolution\Bluesky\Crypto\Format;

use phpseclib3\Crypt\EC\BaseCurves\Base as BaseCurve;
use phpseclib3\Math\PrimeField\Integer;

/**
 * phpseclib custom key format.
 */
final class Compress
{
    /**
     * @param  array<Integer, Integer>  $publicKey
     */
    public static function savePublicKey(BaseCurve $curve, array $publicKey, array $options = []): string
    {
        $prefix = $publicKey[1]->isOdd() ? '03' : '02';

        // 32
        $length = $curve->getLengthInBytes();

        $hexString = str_pad($publicKey[0]->toHex(), $length, '0', STR_PAD_LEFT);

        return $prefix.$hexString;
    }
}
