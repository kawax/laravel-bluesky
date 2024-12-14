<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use Psr\Http\Message\StreamInterface;
use Throwable;

/**
 * @internal
 *
 * @link https://github.com/multiformats/unsigned-varint
 */
final class Varint
{
    public static function encode(int $x): string
    {
        $x = new BigInteger($x);

        $x80 = new BigInteger(0x80);
        $xFF = new BigInteger(0xFF);

        $bytes = [];
        $i = 0;

        while ($x->compare($x80) >= 0) {
            $bytes[$i] = $x->bitwise_and($xFF)->bitwise_or($x80)->toBytes();
            $x = $x->bitwise_rightShift(7);
            $i++;
        }

        $bytes[$i] = $x->bitwise_and($xFF)->toBytes();

        return implode('', $bytes);
    }

    /**
     * @throws Throwable
     */
    public static function decode(string $str): int
    {
        $buf = unpack('C*', $str);
        $x = new BigInteger(0);
        $s = 0;

        foreach ($buf as $i => $b) {
            throw_if($i >= 9);

            if ($b < 0x80) {
                throw_if($b === 0 && $s > 0);

                return intval($x->bitwise_or((new BigInteger($b))->bitwise_leftShift($s))->toString());
            }

            $x = $x->bitwise_or((new BigInteger($b))->bitwise_and(new BigInteger(0x7F))->bitwise_leftShift($s));
            $s += 7;
        }

        throw new InvalidArgumentException(); // @codeCoverageIgnore
    }

    /**
     * Decode the beginning of the stream and move the position.
     */
    public static function decodeStream(StreamInterface $stream): int
    {
        $str = $stream->read(8);
        $int = self::decode($str);
        $len = strlen(self::encode($int));
        $stream->seek($len - 8, SEEK_CUR);

        return $int;
    }
}
