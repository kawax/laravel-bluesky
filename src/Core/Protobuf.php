<?php
declare(strict_types=1);

namespace Revolution\Bluesky\Core;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Revolution\Bluesky\Core\Protobuf\Decoder;
use Revolution\Bluesky\Core\Protobuf\Encoder;
use Throwable;

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
    public static function decode(StreamInterface|string $stream): array
    {
        return app(Decoder::class)->decode(Utils::streamFor($stream));
    }

    public static function encode(array $node): string
    {
        return app(Encoder::class)->encodeNode($node);
    }
}
