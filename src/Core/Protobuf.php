<?php

namespace Revolution\Bluesky\Core;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Arr;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;
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
    /**
     * @throws Throwable
     */
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
            'Links' => $links,
        ];

        if (! is_null($data)) {
            $node['Data'] = $data;
        }

        rescue(fn () => $stream->close());

        return $node;
    }

    private static function decodeKey(StreamInterface $stream): array
    {
        $varint = Varint::decodeStream($stream);

        $wireType = $varint & 0x7;
        $fieldNum = $varint >> 3;

        return [$wireType, $fieldNum];
    }

    private static function decodeBytes(StreamInterface $stream): string
    {
        $varint = Varint::decodeStream($stream);

        return $stream->read($varint);
    }

    /**
     * @throws Throwable
     */
    private static function decodeLink(StreamInterface $stream): array
    {
        $link = [];

        while ($stream->getSize() > $stream->tell()) {
            [$wireType, $fieldNum] = self::decodeKey($stream);

            if ($fieldNum === 1) {
                throw_if(Arr::has($link, ['Hash', 'Name', 'Tsize']));
                throw_unless($wireType === 2);

                $link['Hash'] = Multibase::encode(Multibase::BASE32, self::decodeBytes($stream));
            } elseif ($fieldNum === 2) {
                throw_if(Arr::has($link, ['Name', 'Tsize']));
                throw_unless($wireType === 2);

                $link['Name'] = self::decodeBytes($stream);
            } elseif ($fieldNum === 3) {
                throw_if(Arr::has($link, ['Tsize']));
                throw_unless($wireType === 0);

                $link['Tsize'] = Varint::decodeStream($stream);
            } else {
                throw new RuntimeException();
            }
        }

        return $link;
    }
}
