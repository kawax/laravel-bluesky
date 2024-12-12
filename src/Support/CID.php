<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use Throwable;
use YOCLIB\Multiformats\Multibase\Multibase;

/**
 * Content ID.
 *
 * @link https://atproto.com/specs/data-model#link-and-cid-formats
 */
final class CID
{
    public const CID_V1 = 0x01;

    protected const SHA2_256 = 0x12;

    public const RAW = 0x55;

    public const DAG_CBOR = 0x71;

    /**
     * Encode to a specific format.
     *
     * ```
     * CIDv1
     * multi codec: raw or dag-cbor
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

    public static function verify(string $data, string $cid, int $codec = self::RAW): bool
    {
        return self::encode(data: $data, codec: $codec) === $cid;
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
     *
     * @throws Throwable
     */
    public static function decode(string $cid): array
    {
        $bytes = Multibase::decode($cid);

        $version = Varint::decode(substr($bytes, 0, 1));
        throw_unless(self::CID_V1 === $version);

        $codec = Varint::decode(substr($bytes, 1, 1));
        $hash_algo = Varint::decode(substr($bytes, 2, 1));
        $hash_length = Varint::decode(substr($bytes, 3, 1));
        $hash = bin2hex(substr($bytes, 4, $hash_length));

        return compact(
            'version',
            'codec',
            'hash_algo',
            'hash_length',
            'hash',
        );
    }
}
