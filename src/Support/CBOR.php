<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use GuzzleHttp\Psr7\Utils;
use Revolution\Bluesky\Support\CBOR\Decoder;
use Revolution\Bluesky\Support\CBOR\Encoder;
use Revolution\Bluesky\Support\CBOR\Normalizer;

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

    public static function normalize(mixed $data): mixed
    {
        return app()->call(Normalizer::class, compact('data'));
    }
}
