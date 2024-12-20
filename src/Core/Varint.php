<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Core;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

/**
 * unsigned varint (VARiable INTeger).
 *
 * @internal
 *
 * @link https://github.com/multiformats/unsigned-varint
 */
final class Varint
{
    private const MAX_LEN = 9;

    public static function encode(int $x): string
    {
        // In PHP, if the value exceeds PHP_INT_MAX=9223372036854775807, it will no longer be an int type and a TypeError will be thrown, so there is no need to check here.

        $bytes = '';

        while ($x >= 0x80) {
            $bytes .= chr(($x & 0xFF) | 0x80);
            $x >>= 7;
        }

        $bytes .= chr($x & 0xFF);

        return $bytes;
    }

    public static function decode(string $bytes): int
    {
        if (strlen($bytes) > self::MAX_LEN) {
            throw new InvalidArgumentException();
        }

        $x = 0;
        $s = 0;

        foreach (str_split($bytes) as $i => $b) {
            $b = ord($b);

            if ($i === self::MAX_LEN - 1 && $b >= 0x80) {
                throw new InvalidArgumentException();
            }

            if ($b < 0x80) {
                if ($b === 0 && $s > 0) {
                    throw new InvalidArgumentException();
                }

                return $x | ($b << $s);
            }

            $x |= ($b & 0x7F) << $s;
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

        $bytes = $stream->read(9);
        $x = self::decode($bytes);
        $stream->seek($start + strlen(self::encode($x)));

        return $x;
    }
}
