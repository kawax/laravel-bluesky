<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Core;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Revolution\Bluesky\Core\CBOR\Decoder;
use Revolution\Bluesky\Core\CBOR\Encoder;
use Revolution\Bluesky\Core\CBOR\Normalizer;

/**
 * DAG-CBOR.
 *
 * @link https://ipld.io/specs/codecs/dag-cbor/spec/
 */
final class CBOR
{
    public static function encode(mixed $data): string
    {
        return app(Encoder::class)->encode($data);
    }

    /**
     * If the CBOR contains multiple data items, decode only the first item and return an array with the remainder left as is.
     *
     * ```
     * [$first, $remainder] = CBOR::decodeFirst($data);
     *
     * $first
     * decoded data
     *
     * $remainder
     * string or empty
     *
     * // If you decode the $remainder further, you can get the second data.
     * [$second, $remainder] = CBOR::decodeFirst($remainder);
     * ```
     *
     * @return array{0: mixed, 1: ?string}
     */
    public static function decodeFirst(StreamInterface|string $data): array
    {
        return app(Decoder::class)->decodeFirst(Utils::streamFor($data));
    }

    /**
     * Decodes a CBOR containing only a single data item.
     */
    public static function decode(StreamInterface|string $data): mixed
    {
        return app(Decoder::class)->decode(Utils::streamFor($data));
    }

    /**
     * Decode all data from CBOR containing multiple items.
     */
    public static function decodeAll(StreamInterface|string $data): array
    {
        return app(Decoder::class)->decodeAll(Utils::streamFor($data));
    }

    public static function normalize(mixed $data): mixed
    {
        return app()->call(Normalizer::class, compact('data'));
    }
}
