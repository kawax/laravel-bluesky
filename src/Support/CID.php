<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use YOCLIB\Multiformats\Multibase\Multibase;

/**
 * Content ID.
 *
 * @link https://atproto.com/specs/data-model#link-and-cid-formats
 */
class CID
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

        $version = self::varint(self::CID_V1);
        $code = self::varint($codec);
        $type = self::varint(self::SHA2_256);
        $length = self::varint($hash_length);

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
     *     'version' => 'varint bin',
     *     'codec' => 'bin',
     *     'hash_algo' => 'bin',
     *     'hash_length' => 'bin',
     *     'hash' => 'hex',
     * ]
     * ```
     *
     * @return array{version: string, codec: string, hash_algo: string, hash_length: string, hash: string}
     */
    public static function decode(string $cid): array
    {
        $bytes = Multibase::decode($cid);

        $version = substr($bytes, 0, 1);
        $codec = substr($bytes, 1, 1);
        $hash_algo = substr($bytes, 2, 1);
        $hash_length = substr($bytes, 3, 1);
        $hash = bin2hex(substr($bytes, 4));

        return compact(
            'version',
            'codec',
            'hash_algo',
            'hash_length',
            'hash',
        );
    }

    public static function varint(int $x): string
    {
        $buf = [];
        $i = 0;

        while ($x >= 0x80) {
            $buf[$i] = $x & 0xFF | 0x80;
            $x = $x >> 7;
            $i++;
        }

        $buf[$i] = $x & 0xFF;

        return pack('C*', ...$buf);
    }
}
