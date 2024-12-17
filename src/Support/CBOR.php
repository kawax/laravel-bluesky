<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use GuzzleHttp\Psr7\Utils;
use Revolution\Bluesky\Support\CBOR\BytesWrapper;
use Revolution\Bluesky\Support\CBOR\CIDLinkWrapper;
use Revolution\Bluesky\Support\CBOR\Decoder;
use Revolution\Bluesky\Support\CBOR\Encoder;

final class CBOR
{
    public static function encode(mixed $data): string
    {
        return (new Encoder())->encode($data);
    }

    public static function decodeFirst(string $data): array
    {
        $decoder = new Decoder(Utils::streamFor($data));

        return $decoder->decodeFirst();
    }

    public static function decode(string $data): mixed
    {
        $decoder = new Decoder(Utils::streamFor($data));

        return $decoder->decode();
    }

    /**
     * Match the official go-repo-export output.
     *
     * @link https://github.com/bluesky-social/cookbook/tree/main/go-repo-export
     *
     * @todo
     */
    public static function normalize(mixed $data): mixed
    {
        if (is_array($data)) {
            return collect($data)->map(function ($item, $key) {
                if (in_array($key, ['ref', 'link'], true) && $item instanceof CIDLinkWrapper) {
                    return $item->toArray();
                }
                if (in_array($key, ['v', 't', 'l', 'data'], true) && $item instanceof CIDLinkWrapper) {
                    return $item->mst();
                }
                if ($item instanceof CIDLinkWrapper) {
                    return $item->cid();
                }
                if ($key === 'sig' && $item instanceof BytesWrapper) {
                    return $item->encode();
                }
                if ($item instanceof BytesWrapper) {
                    return $item->bytes();
                }

                return self::normalize($item);
            })->toArray();
        }

        return $data;
    }
}
