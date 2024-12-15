<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use GuzzleHttp\Psr7\Utils;
use YOCLIB\Multiformats\Multibase\Multibase;

/**
 * Content ID.
 *
 * @link https://atproto.com/specs/data-model#link-and-cid-formats
 */
final class CID
{
    public const CID_V1 = 0x01;

    public const SHA2_256 = 0x12;

    public const RAW = 0x55;

    public const DAG_CBOR = 0x71;

    public const DAG_PB = 0x20;

    /**
     * Encode to a specific format.
     *
     * ```
     * CIDv1
     * multi codec: raw
     * multi hash: sha256
     * ```
     */
    public static function encode(string $data, int $codec = self::RAW): string
    {
        $hash = hash(algo: 'sha256', data: $data, binary: true);
        $hash_length = strlen($hash);

        $version = Varint::encode(self::CID_V1);
        $code = Varint::encode($codec);
        $type = Varint::encode(self::SHA2_256);
        $length = Varint::encode($hash_length);

        $bytes = $version.$code.$type.$length.$hash;

        return Multibase::encode(Multibase::BASE32, $bytes);
    }

    public static function verify(string $data, string $cid, ?int $codec = self::RAW): bool
    {
        // Detect codec
        if (is_null($codec)) {
            $codec = data_get(self::decode($cid), 'codec', self::RAW);
        }

        return hash_equals($cid, self::encode(data: $data, codec: $codec));
    }

    /**
     * Decode CID.
     *
     * Not recommended to verify using decoded data.
     * Compare the CID string as is.
     *
     * ```
     * $decode = CID::decode('b***');
     *
     * [
     *     'version' => int,
     *     'codec' => int,
     *     'hash_algo' => int,
     *     'hash_length' => int,
     *     'hash' => 'hex',
     * ]
     * ```
     *
     * @return array{version: int, codec: int, hash_algo: int, hash_length: int, hash: string}
     */
    public static function decode(string $cid): array
    {
        $stream = Utils::streamFor(Multibase::decode($cid));

        $version = Varint::decodeStream($stream);
        $codec = Varint::decodeStream($stream);
        $hash_algo = Varint::decodeStream($stream);
        $hash_length = Varint::decodeStream($stream);
        $hash = bin2hex($stream->read($hash_length));

        return compact(
            'version',
            'codec',
            'hash_algo',
            'hash_length',
            'hash',
        );
    }
}
