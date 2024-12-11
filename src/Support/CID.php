<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use Acaisia\Multiformats\Varint\Varint;
use YOCLIB\Multiformats\Multibase\Multibase;

/**
 * Content ID.
 *
 * @link https://atproto.com/specs/data-model#link-and-cid-formats
 */
class CID
{
    public const CID_V1 = "\x01";

    protected const SHA256 = "\x12";

    protected const RAW = "\x55";

    /**
     * Encode to a specific format.
     *
     * ```
     * CIDv1
     * multicodec: raw
     * multihash: sha256
     * ```
     */
    public static function encode(string $data): string
    {
        $hash = hash(algo: 'sha256', data: $data, binary: true);
        $hash_length = strlen($hash);

        $varint_hash = (string) Varint::fromInteger(intval(bin2hex(self::SHA256), 16))->toByteArray();
        $varint_length = (string) Varint::fromInteger($hash_length)->toByteArray();
        $varint = $varint_hash.$varint_length;

        $bytes = self::CID_V1.self::RAW.$varint.$hash;

        return Multibase::encode(Multibase::BASE32, $bytes);
    }

    public static function verify(string $data, string $cid): bool
    {
        return self::encode($data) === $cid;
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
     *     'version' => 'bin',
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
        $hash = bin2hex(substr($bytes, 4, intval(bin2hex($hash_length), 16)));

        return compact(
            'version',
            'codec',
            'hash_algo',
            'hash_length',
            'hash',
        );
    }
}
