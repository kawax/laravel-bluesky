<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Str;
use YOCLIB\Multiformats\Multibase\Multibase;

/**
 * Content ID.
 *
 * @link https://atproto.com/specs/data-model#link-and-cid-formats
 */
final class CID
{
    public const CID_V0 = 0x00;

    public const V0_LEADING = "\x12\x20";

    public const CID_V1 = 0x01;

    public const SHA2_256 = 0x12;

    public const RAW = 0x55;

    public const DAG_CBOR = 0x71;

    public const DAG_PB = 0x20;

    /**
     * Encode CIDv1.
     *
     * ```
     * CIDv1
     * multi codec: RAW or DAG-CBOR
     * multi hash: sha256
     * ```
     *
     * @param  string  $data  Raw data, or DAG-CBOR encoded data.
     */
    public static function encode(string $data, int $codec = self::RAW): string
    {
        $hash = hash(algo: 'sha256', data: $data, binary: true);
        $hash_length = strlen($hash);

        $version = Varint::encode(self::CID_V1);
        $code = Varint::encode($codec);
        $algo = Varint::encode(self::SHA2_256);
        $length = Varint::encode($hash_length);

        $bytes = $version.$code.$algo.$length.$hash;

        return Multibase::encode(Multibase::BASE32, $bytes);
    }

    /**
     * Encode CIDv0.
     *
     * @param  string  $data  DAG-PB encoded data. Starts with "Qm"
     */
    public static function encodeV0(string $data): string
    {
        $hash = hash(algo: 'sha256', data: $data, binary: true);

        return Multibase::encode(Multibase::BASE58BTC, self::V0_LEADING.$hash, false);
    }

    /**
     * Verify CIDv1.
     *
     * @param  string  $data  Raw data, or DAG-CBOR encoded data.
     */
    public static function verify(string $data, string $cid, ?int $codec = self::RAW): bool
    {
        // Detect codec
        if (is_null($codec)) {
            $codec = data_get(self::decode($cid), 'codec', self::RAW);
        }

        return hash_equals($cid, self::encode(data: $data, codec: $codec));
    }

    /**
     * Verify CIDv0.
     *
     * @param  string  $data  DAG-PB encoded data.
     */
    public static function verifyV0(string $data, string $cid): bool
    {
        return hash_equals($cid, self::encodeV0(data: $data));
    }

    /**
     * Decode CIDv1.
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

    /**
     * Decode CIDv0.
     *
     * ```
     * $decode = CID::decodeV0('Qm***');
     *
     * [
     *     'version' => 0,
     *     'codec' => 32,
     *     'hash_algo' => 18,
     *     'hash_length' => 32,
     *     'hash' => 'hex',
     * ]
     * ```
     *
     * @param  string  $cid  CID starting with "Qm"
     * @return array{version: int, codec: int, hash_algo: int, hash_length: int, hash: string}
     */
    public static function decodeV0(string $cid): array
    {
        if (Str::startsWith($cid, Multibase::BASE58BTC)) {
            $cid = Str::chopStart($cid, Multibase::BASE58BTC);
        }

        $stream = Utils::streamFor(Multibase::decode($cid, Multibase::BASE58BTC));

        $version = 0;
        $hash_algo = Varint::decodeStream($stream);
        $codec = Varint::decodeStream($stream);
        $hash_length = 32;
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
