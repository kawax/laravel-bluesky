<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use YOCLIB\Multiformats\Multibase\Multibase;

/**
 * @link https://ipld.io/specs/transport/car/carv1/
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
     *     'cid base32' => [Contains CBORObject],
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
     * Decode roots.
     *
     * @return list<string>
     */
    public static function decodeRoots(StreamInterface|string $data): array
    {
        if (! $data instanceof StreamInterface) {
            $data = Utils::streamFor($data);
        }

        $data->rewind();

        $header_length = Varint::decode($data->read(8));
        $varint_len = strlen(Varint::encode($header_length));
        $data->seek($varint_len);

        $header_bytes = $data->read($header_length);
        $header = CBOR::decode($header_bytes)->normalize();

        $roots = data_get($header, 'roots');
        $roots = collect($roots)->map(function ($root) {
            $cid = $root->getValue()->getValue();
            $cid = substr($cid, 1); // remove first 0x00

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

        $header_length = Varint::decode($data->read(1));

        $offset = 1 + $header_length;

        while ($offset < $data->getSize()) {
            $data->seek($offset);

            $block_varint = Varint::decode($data->read(8));
            $varint_len = strlen(Varint::encode($block_varint));

            $data->seek($offset + $varint_len);

            // CIDv0 is not supported
            $cid_0 = $data->read(2) === "\x12\x20";
            if ($cid_0) {
                $offset += $varint_len + $block_varint;

                continue;
            }

            $data->seek(-2, SEEK_CUR);

            $cid_version = rescue(fn () => Varint::decode($data->read(1)));

            if ($cid_version !== CID::CID_V1) {
                throw new InvalidArgumentException('Invalid CAR.');
            }

            $cid_codec = rescue(fn () => Varint::decode($data->read(1)));

            // DAG-PB is not supported
            if ($cid_codec === CID::DAG_PB) {
                $offset += $varint_len + $block_varint;

                continue;
            }

            $cid_hash_type = rescue(fn () => Varint::decode($data->read(1)));
            $cid_hash_length = rescue(fn () => Varint::decode($data->read(1)));

            $data->seek($offset + $varint_len);
            $cid_bytes = $data->read(4 + $cid_hash_length);
            $cid = Multibase::encode(Multibase::BASE32, $cid_bytes);

            $block_length = $block_varint - 4 - $cid_hash_length;
            $block_bytes = $data->read($block_length);

            if ($cid_codec === CID::RAW) {
                $block = $block_bytes;
            } else {
                $block = rescue(fn () => CBOR::decode($block_bytes)->normalize());
            }

            $offset += $varint_len + $block_varint;

            if (! empty($block)) {
                yield $cid => $block;
            }
        }
    }
}
