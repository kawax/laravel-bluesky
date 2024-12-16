<?php

namespace Revolution\Bluesky\Support;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Arr;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use YOCLIB\Multiformats\Multibase\Multibase;

/**
 * Protobuf in CBOR-PB.
 *
 * This should not be used in Bluesky. Implemented for testing purposes.
 *
 * @internal
 *
 * @link https://github.com/ipld/js-dag-pb
 */
final class Protobuf
{
    public static function decode(StreamInterface $stream): array
    {
        $links = [];
        $linksBeforeData = false;
        $data = null;

        while ($stream->getSize() > $stream->tell()) {
            [$wireType, $fieldNum] = self::decodeKey($stream);

            throw_unless($wireType === 2);
            throw_unless($fieldNum === 1 || $fieldNum === 2);

            if ($fieldNum === 1) {
                throw_unless(is_null($data));

                $data = self::decodeBytes($stream);

                if (filled($links)) {
                    $linksBeforeData = true;
                }
            } elseif ($fieldNum === 2) {
                throw_if($linksBeforeData);

                $bytes = self::decodeBytes($stream);
                $links[] = self::decodeLink(Utils::streamFor($bytes));
            }
        }

        $node = [
            'links' => $links,
        ];

        if (! is_null($data)) {
            $node['data'] = $data;
        }

        return $node;
    }

    private static function decodeVarint(StreamInterface $stream): int
    {
        $v = 0;

        for ($shift = 0; ; $shift += 7) {
            throw_if($shift >= 64);
            throw_if($stream->eof());

            $b = intval(bin2hex($stream->read(1)), 16);
            $v += $shift < 28 ? ($b & 0x7F) << $shift : ($b & 0x7F) * (2 ** $shift);
            if ($b < 0x80) {
                break;
            }
        }

        return $v;
    }

    private static function decodeKey(StreamInterface $stream): array
    {
        $varint = self::decodeVarint($stream);

        $wireType = $varint & 0x7;
        $fieldNum = $varint >> 3;

        return [$wireType, $fieldNum];
    }

    private static function decodeBytes(StreamInterface $stream): string
    {
        $varint = self::decodeVarint($stream);

        return $stream->read($varint);
    }

    private static function decodeLink(StreamInterface $stream): array
    {
        $link = [];

        while ($stream->getSize() > $stream->tell()) {
            [$wireType, $fieldNum] = self::decodeKey($stream);

            if ($fieldNum === 1) {
                throw_if(Arr::has($link, ['hash', 'name', 'tsize']));
                throw_unless($wireType === 2);

                $link['hash'] = Multibase::encode(Multibase::BASE32, self::decodeBytes($stream));
            } elseif ($fieldNum === 2) {
                throw_if(Arr::has($link, ['name', 'tsize']));
                throw_unless($wireType === 2);

                $link['name'] = self::decodeBytes($stream);
            } elseif ($fieldNum === 3) {
                throw_if(Arr::has($link, ['tsize']));
                throw_unless($wireType === 0);

                $link['tsize'] = self::decodeVarint($stream);
            } else {
                throw new RuntimeException();
            }
        }

        return $link;
    }
}
