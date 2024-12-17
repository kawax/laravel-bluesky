<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Core;

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
    public const V0 = 0x00;

    public const V0_LEADING = "\x12\x20";

    public const ZERO = "\x00";

    public const V1 = 0x01;

    public const SHA2_256 = 0x12;

    public const RAW = 0x55;

    public const DAG_CBOR = 0x71;

    public const DAG_PB = 0x20;

    /**
     * Encode CIDv1 or v0.
     *
     * ```
     * $cid_v1_raw = CID::encode($raw_bytes);
     * $cid_v1_cbor = CID::encode($cbor_bytes, CID::DAG_CBOR);
     * $cid_v1 = CID::encode(data: $raw_bytes, codec: CID::RAW, ver: CID::V1);
     *
     * // "b..."
     * ```
     * ```
     * $cid_v0 = CID::encode(data: $pb_bytes, ver: CID::V0);
     *
     * // "Qm..."
     * ```
     */
    public static function encode(string $data, int $codec = self::RAW, int $ver = self::V1): string
    {
        return match ($ver) {
            self::V0 => self::encodeV0($data),
            default => self::encodeV1($data, $codec),
        };
    }

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
    public static function encodeV1(string $data, int $codec = self::RAW): string
    {
        $hash = hash(algo: 'sha256', data: $data, binary: true);
        $hash_length = strlen($hash);

        $version = Varint::encode(self::V1);
        $code = Varint::encode($codec);
        $algo = Varint::encode(self::SHA2_256);
        $length = Varint::encode($hash_length);

        $bytes = $version.$code.$algo.$length.$hash;

        return Multibase::encode(Multibase::BASE32, $bytes);
    }

    /**
     * Encode CIDv0.
     *
     * @param  string  $data  DAG-PB encoded data.
     */
    public static function encodeV0(string $data): string
    {
        $hash = hash(algo: 'sha256', data: $data, binary: true);

        return Multibase::encode(Multibase::BASE58BTC, self::V0_LEADING.$hash, false);
    }

    /**
     * Verify CID.
     *
     * ```
     * if (CID::verify($raw_data, $cid, codec: CID::RAW) {
     *
     * }
     * ```
     *
     * @param  string  $data  Target encoded bytes data. RAW, DAG-CBOR or DAG-PG
     * @param  string  $cid  CIDv1 or v0
     */
    public static function verify(string $data, string $cid, ?int $codec = null): bool
    {
        return match (self::detect($cid)) {
            self::V0 => self::verifyV0($data, $cid),
            default => self::verifyV1($data, $cid, $codec),
        };
    }

    /**
     * Verify CIDv1.
     *
     * @param  string  $data  Raw data, or DAG-CBOR encoded data.
     */
    public static function verifyV1(string $data, string $cid, ?int $codec = null): bool
    {
        // Detect codec
        if (is_null($codec)) {
            $codec = data_get(self::decode($cid), 'codec', self::DAG_CBOR);
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
        return hash_equals($cid, self::encode(data: $data, ver: self::V0));
    }

    /**
     * Decode CIDv1 or v0.
     *
     * @return array{version: int, codec: int, hash_algo: int, hash_length: int, hash: string}
     */
    public static function decode(string $cid): array
    {
        return match (self::detect($cid)) {
            self::V0 => self::decodeV0($cid),
            default => self::decodeV1($cid),
        };
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
    public static function decodeV1(string $cid): array
    {
        $stream = Utils::streamFor(Multibase::decode($cid));

        $version = Varint::decodeStream($stream);
        $codec = Varint::decodeStream($stream);
        $hash_algo = Varint::decodeStream($stream);
        $hash_length = Varint::decodeStream($stream);
        $hash = bin2hex($stream->read($hash_length));

        rescue(fn () => $stream->close());

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

        rescue(fn () => $stream->close());

        return compact(
            'version',
            'codec',
            'hash_algo',
            'hash_length',
            'hash',
        );
    }

    /**
     * Decode to bytes.
     */
    public static function decodeBytes(string $cid): string
    {
        return match (self::detect($cid)) {
            self::V0 => Multibase::decode($cid, Multibase::BASE58BTC),
            default => Multibase::decode($cid),
        };
    }

    /**
     * Detect CID version.
     *
     * @return int 0: CIDv0, 1: CIDv1
     */
    public static function detect(string $cid): int
    {
        if (strlen($cid) === 46 && Str::startsWith($cid, 'Qm')) {
            return self::V0;
        }

        $bytes = Multibase::decode($cid);
        throw_if(str_starts_with($bytes, "\x12"));

        if (strlen($bytes) === 34 && str_starts_with($bytes, self::V0_LEADING)) {
            return self::V0;
        }

        return ord($bytes);
    }
}
