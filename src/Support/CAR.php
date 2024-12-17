<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use YOCLIB\Multiformats\Multibase\Multibase;

/**
 * @link https://ipld.io/specs/transport/car/carv1/
 * @link https://github.com/ipld/go-car
 * @link https://github.com/ipld/js-car
 */
final class CAR
{
    /**
     * Decode CAR data.
     *
     * Limited implementation, can be used to decode downloaded CAR files and Firehose.
     *
     * ```
     * [$roots, $blocks] = CAR::decode('data');
     *
     * $roots
     * [
     *     0 => 'cid',
     * ]
     *
     * $blocks
     * [
     *     'cid' => [],
     *     'cid' => [],
     * ]
     * ```
     *
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    public static function decode(StreamInterface|string $data): array
    {
        $data = Utils::streamFor($data);

        $data->rewind();

        $roots = self::decodeRoots($data);

        $blocks = iterator_to_array(self::blockIterator($data));

        rescue(fn () => $data->close());

        return [$roots, $blocks];
    }

    /**
     * Decode header.
     *
     * @return array{rootes: array, version: string}
     */
    public static function decodeHeader(StreamInterface|string $data): array
    {
        $data = Utils::streamFor($data);

        $data->rewind();

        $header_length = Varint::decodeStream($data);

        $header_bytes = $data->read($header_length);

        return CBOR::decode($header_bytes);
    }

    /**
     * Decode roots.
     *
     * @return list<string>
     */
    public static function decodeRoots(StreamInterface|string $data): array
    {
        $header = self::decodeHeader($data);

        return data_get($header, 'roots', []);
    }

    /**
     * Decode data block. Returns iterable.
     *
     * ```
     * use Illuminate\Support\Arr;
     *
     * foreach (CAR::blockIterator($data) as $cid => $block) {
     *     if (Arr::exists($block, '$type')) {
     *     }
     * }
     * ```
     * ```
     * $blocks = iterator_to_array(CAR::blockIterator($data));
     * ```
     *
     * @return iterable<string, mixed>
     */
    public static function blockIterator(StreamInterface|string $data): iterable
    {
        $data = Utils::streamFor($data);

        $data->rewind();

        $header_length = Varint::decodeStream($data);
        $data->seek($header_length, SEEK_CUR);

        while ($data->getSize() > $data->tell()) {
            $start = $data->tell();

            $block_varint = Varint::decodeStream($data);

            $cid_0 = $data->read(2) === CID::V0_LEADING;

            $data->seek($start);

            if ($cid_0) {
                // CID v0
                [$cid, $block] = self::decodeBlockV0($data);
            } else {
                // CID v1
                [$cid, $block] = self::decodeBlockV1($data);
            }

            if (! is_null($block)) {
                yield $cid => $block;
            }
        }
    }

    private static function decodeBlockV1(StreamInterface $data): array
    {
        $block_varint = Varint::decodeStream($data);

        $start = $data->tell();

        $cid_version = Varint::decodeStream($data);

        if ($cid_version !== CID::V1) {
            throw new InvalidArgumentException('Invalid CAR.');
        }

        $cid_codec = Varint::decodeStream($data);
        $cid_hash_type = Varint::decodeStream($data);
        $cid_hash_length = Varint::decodeStream($data);

        $cid_length = $data->tell() - $start;

        $data->seek(-$cid_length, SEEK_CUR);
        $cid_bytes = $data->read($cid_length + $cid_hash_length);
        $cid = Multibase::encode(Multibase::BASE32, $cid_bytes);

        $block_length = $block_varint - $cid_length - $cid_hash_length;
        $block_bytes = $data->read($block_length);

        if ($cid_codec === CID::RAW) {
            if (! CID::verify($block_bytes, $cid, codec: CID::RAW)) {
                return [null, null];
            }

            $block = $block_bytes;
        } elseif ($cid_codec === CID::DAG_CBOR) {
            if (! CID::verify($block_bytes, $cid, codec: CID::DAG_CBOR)) {
                return [null, null];
            }

            $block = CBOR::decode($block_bytes);
        } else {
            throw new InvalidArgumentException('Invalid CAR.');
        }

        return [$cid, $block];
    }

    private static function decodeBlockV0(StreamInterface $data): array
    {
        $block_varint = Varint::decodeStream($data);

        // $cid_codec = CID::DAG_PB;
        // $cid_hash_type = CID::SHA2_256;
        // $cid_hash_length = 32;

        $cid_bytes = $data->read(34);
        $cid = Multibase::encode(Multibase::BASE58BTC, $cid_bytes, false);
        $block_bytes = $data->read($block_varint - 34);

        throw_unless(CID::verifyV0($block_bytes, $cid));

        $block = Protobuf::decode(Utils::streamFor($block_bytes));

        return [$cid, $block];
    }
}
