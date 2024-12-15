<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Str;
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
     * Only CIDv1, DAG-CBOR/RAW, and SHA2-256 format are supported.
     *
     * ```
     * [$roots, $blocks] = CAR::decode('data');
     *
     * $roots
     * [
     *     0 => 'cid base32',
     * ]
     *
     * $blocks
     * [
     *     'cid base32' => [],
     *     'cid base32' => [],
     * ]
     * ```
     *
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    public static function decode(StreamInterface|string $data): array
    {
        if (! $data instanceof StreamInterface) {
            $data = Utils::streamFor($data);
        }

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
        if (! $data instanceof StreamInterface) {
            $data = Utils::streamFor($data);
        }

        $data->rewind();

        $header_length = Varint::decodeStream($data);

        $header_bytes = $data->read($header_length);

        return CBOR::decode($header_bytes)->normalize();
    }

    /**
     * Decode roots.
     *
     * @return list<string>
     */
    public static function decodeRoots(StreamInterface|string $data): array
    {
        $header = self::decodeHeader($data);

        $roots = data_get($header, 'roots');

        $roots = collect($roots)->map(function ($root) {
            $cid = $root->getValue()->getValue();
            $cid = Str::ltrim($cid, "\x00"); // remove first 0x00

            return Multibase::encode(Multibase::BASE32, $cid);
        });

        return $roots->toArray();
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
        if (! $data instanceof StreamInterface) {
            $data = Utils::streamFor($data);
        }

        $data->rewind();

        $header_length = Varint::decodeStream($data);
        $data->seek($header_length, SEEK_CUR);

        while ($data->getSize() > $data->tell()) {
            $block_varint = Varint::decodeStream($data);

            $start = $data->tell();

            // CIDv0 is not supported
            $cid_0 = $data->read(2) === "\x12\x20";
            if ($cid_0) {
                $data->seek($start + $block_varint);
                continue;
            }
            $data->seek(-2, SEEK_CUR);

            $cid_version = rescue(fn () => Varint::decodeStream($data));

            if ($cid_version !== CID::CID_V1) {
                throw new InvalidArgumentException('Invalid CAR.');
            }

            $cid_codec = rescue(fn () => Varint::decodeStream($data));

            // DAG-PB is not supported
            if ($cid_codec === CID::DAG_PB) {
                $data->seek($start + $block_varint);

                continue;
            }

            $cid_hash_type = rescue(fn () => Varint::decodeStream($data));
            $cid_hash_length = rescue(fn () => Varint::decodeStream($data));

            $cid_length = $data->tell() - $start;

            $data->seek(-$cid_length, SEEK_CUR);
            $cid_bytes = $data->read($cid_length + $cid_hash_length);
            $cid = Multibase::encode(Multibase::BASE32, $cid_bytes);

            $block_length = $block_varint - $cid_length - $cid_hash_length;
            $block_bytes = $data->read($block_length);

            if ($cid_codec === CID::RAW) {
                $block = $block_bytes;
            } elseif ($cid_codec === CID::DAG_CBOR) {
                $block = rescue(fn () => CBOR::normalize(CBOR::decode($block_bytes)->normalize()));
            } else {
                throw new InvalidArgumentException('Invalid CAR.');
            }

            if (! is_null($block)) {
                yield $cid => $block;
            }
        }
    }
}
