<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Revolution\Bluesky\Support\CBOR\Decoder;
use Revolution\Bluesky\Support\CBOR\Encoder;
use Revolution\Bluesky\Support\CBOR\Normalizer;

final class CBOR
{
    public static function encode(mixed $data): string
    {
        return app(Encoder::class)->encode($data);
    }

    public static function decodeFirst(StreamInterface|string $data): array
    {
        return app(Decoder::class)->decodeFirst(Utils::streamFor($data));
    }

    public static function decode(StreamInterface|string $data): mixed
    {
        return app(Decoder::class)->decode(Utils::streamFor($data));
    }

    public static function normalize(mixed $data): mixed
    {
        return app()->call(Normalizer::class, compact('data'));
    }
}
