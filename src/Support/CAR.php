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
 * @link https://github.com/mary-ext/atcute/blob/trunk/packages/utilities/car/lib/atproto-repo.ts
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
     *     '<collection>/<rkey>' => [record],
     *     '<collection>/<rkey>' => [record],
     * ]
     * ```
     *
     * @return array{0: list<string>, 1: array<string, array>}
     */
    public static function decode(StreamInterface|string $data): array
    {
        $data = Utils::streamFor($data);

        $data->rewind();

        $roots = self::decodeRoots($data);

        $blocks = iterator_to_array(self::blockMap($data));

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

    /**
     * Unlike {@link CAR::blockIterator()}, this is an iterator for `<collection>/<rkey>` key and record array.
     *
     * Works only Bluesky/AtProto CAR.
     *
     * ```
     * foreach (CAR::blockMap($data) as $key => $record) {
     *     [$collection, $rkey] = explode('/', $key);
     *     $block = data_get($record, 'value');
     *     $cid = data_get($record, 'cid');
     *
     * }
     * ```
     *
     * @return iterable<string, array>
     */
    public static function blockMap(StreamInterface|string $data): iterable
    {
        $data = Utils::streamFor($data);

        $roots = self::decodeRoots($data);
        $blockmap = iterator_to_array(self::blockIterator($data));

        $commit = data_get($blockmap, $roots[0]);
        $did = data_get($commit, 'did');

        if (Identity::isDID($did)) {
            yield from self::walkEntries($blockmap, data_get($commit, 'data./'), $did);
        }
    }

    private static function walkEntries(array $blockmap, string $pointer, string $did): iterable
    {
        $data = data_get($blockmap, $pointer);
        $entries = data_get($data, 'e', []);

        $lastKey = '';

        if (filled(data_get($data, 'l./'))) {
            yield from self::walkEntries($blockmap, data_get($data, 'l./'), $did);
        }

        foreach ($entries as $entry) {
            $key_str = data_get($entry, 'k');
            $key = substr($lastKey, 0, data_get($entry, 'p')).$key_str;

            $lastKey = $key;

            [$collection, $rkey] = explode('/', $key);
            $uri = (string) AtUri::make(repo: $did, collection: $collection, rkey: $rkey);
            $cid = data_get($entry, 'v./');
            $value = data_get($blockmap, $cid);

            // Match the format of getRecord and listRecords.
            $record = compact('uri', 'cid');
            if (! is_null($value)) {
                $record['value'] = $value;
            }
            yield $key => $record;

            if (filled(data_get($entry, 't./'))) {
                yield from self::walkEntries($blockmap, data_get($entry, 't./'), $did);
            }
        }
    }
}
