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

        $bytes = '';

        while ($x->compare($x80) >= 0) {
            $bytes .= $x->bitwise_and($xFF)->bitwise_or($x80)->toBytes();
            $x = $x->bitwise_rightShift(7);
        }

        $bytes .= $x->bitwise_and($xFF)->toBytes();

        return $bytes;
    }

    /**
     * @throws Throwable
     */
    public static function decode(string $bytes): int
    {
        $buf = unpack('C*', $bytes);
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
        $start = $stream->tell();

        $bytes = $stream->read(8);
        $x = self::decode($bytes);
        $stream->seek($start + strlen(self::encode($x)));

        return $x;
    }
}
