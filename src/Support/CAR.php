<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

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
     * Only CIDv1, DAG-CBOR, and SHA2-256 format are supported.
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
     * @return array<list<string>, array<string, array>>
     */
    public static function decode(string $data): array
    {
        $roots = self::decodeRoots($data);

        $blocks = iterator_to_array(self::blockIterator($data));

        return [$roots, $blocks];
    }

    /**
     * @return list<string>
     */
    public static function decodeRoots(string $data): array
    {
        $header_length = Varint::decode(substr($data, 0, 1));
        $header_bytes = substr($data, 1, $header_length);
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
     * @return iterable<string, array>
     */
    public static function blockIterator(string $data): iterable
    {
        $header_length = Varint::decode(substr($data, 0, 1));

        $offset = 1 + $header_length;

        while ($offset < strlen($data)) {
            $block_varint = rescue(fn () => Varint::decode(substr($data, $offset, 1)));
            $cid_version = rescue(fn () => Varint::decode(substr($data, $offset + 1, 1)));
            $cid_codec_varint = rescue(fn () => Varint::decode(substr($data, $offset + 2, 1)));
            $cid_hash_varint = rescue(fn () => Varint::decode(substr($data, $offset + 3, 1)));

            if (empty($block_varint) || $cid_version !== CID::CID_V1 || $cid_hash_varint !== CID::SHA2_256 || $cid_codec_varint !== CID::DAG_CBOR) {
                $offset += 1;
                continue;
            }

            $cid_hash_length = rescue(fn () => Varint::decode(substr($data, $offset + 4, 1)));
            $cid_bytes = substr($data, $offset + 1, 4 + $cid_hash_length);
            $cid = Multibase::encode(Multibase::BASE32, $cid_bytes);

            $block_length = $block_varint - 4 - $cid_hash_length;
            $block_bytes = substr($data, $offset + 1 + 4 + $cid_hash_length, $block_length);
            $block = rescue(fn () => CBOR::decode($block_bytes)->normalize());

            $offset = $offset + 1 + $block_varint;

            if (! empty($block) && is_array($block)) {
                yield $cid => $block;
            }
        }
    }
}