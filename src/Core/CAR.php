<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Core;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use phpseclib3\Crypt\EC;
use Psr\Http\Message\StreamInterface;
use Revolution\Bluesky\Crypto\DidKey;
use Revolution\Bluesky\Crypto\Signature;
use Revolution\Bluesky\Support\AtUri;
use Revolution\Bluesky\Support\Identity;
use RuntimeException;

/**
 * Content Addressable aRchives.
 *
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
     *     'cid' => [],// In Bluesky, this is always an array
     *     'cid' => [],// Other CAR files may be mixed
     * ]
     * ```
     *
     * @return array{0: list<string>, 1: array<string, array>}
     */
    public static function decode(StreamInterface|string $data): array
    {
        $data = Utils::streamFor($data);

        throw_unless($data->isReadable() && $data->isSeekable());

        $data->rewind();

        $roots = self::decodeRoots($data);

        $blocks = iterator_to_array(self::blockIterator($data));

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

        throw_unless($data->isReadable() && $data->isSeekable());

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

        throw_unless($data->isReadable() && $data->isSeekable());

        $data->rewind();

        $header_length = Varint::decodeStream($data);
        $data->seek($header_length, SEEK_CUR);

        $size = $data->getSize();

        while ($size > $data->tell()) {
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

            if (! empty($block)) {
                yield $cid => $block;
            }
        }
    }

    /**
     * @return array{0: string, 1: array}
     */
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
        $cid = CID::encodeBytes($cid_bytes, ver: CID::V1);

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

    /**
     * @return array{0: string, 1: array}
     */
    private static function decodeBlockV0(StreamInterface $data): array
    {
        $block_varint = Varint::decodeStream($data);

        // $cid_codec = CID::DAG_PB;
        // $cid_hash_type = CID::SHA2_256;
        // $cid_hash_length = 32;

        $cid_bytes = $data->read(34);
        $cid = CID::encodeBytes($cid_bytes, ver: CID::V0);
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

        throw_unless($data->isReadable() && $data->isSeekable());

        [$roots, $blockmap] = self::decode($data);

        $commit = data_get($blockmap, $roots[0]);
        $did = data_get($commit, 'did');

        if (Identity::isDID($did)) {
            $pointer = data_get($commit, 'data./');
            yield from self::walkEntries($blockmap, $pointer, $did);
        }
    }

    /**
     * @return iterable<string, array>
     */
    private static function walkEntries(array $blockmap, string $pointer, string $did): iterable
    {
        $data = data_get($blockmap, $pointer);
        /** @var array $entries */
        $entries = data_get($data, 'e', []);

        $lastKey = '';

        if (filled($left = data_get($data, 'l./'))) {
            yield from self::walkEntries($blockmap, $left, $did);
        }

        foreach ($entries as $entry) {
            /** @var string $key_str */
            $key_str = data_get($entry, 'k');
            $key = substr($lastKey, 0, data_get($entry, 'p')).$key_str;

            $lastKey = $key;

            [$collection, $rkey] = explode('/', $key);
            $uri = AtUri::make(repo: $did, collection: $collection, rkey: $rkey)->toString();
            $cid = data_get($entry, 'v./');
            $value = data_get($blockmap, $cid);

            // Match the format of getRecord and listRecords.
            $record = compact('uri', 'cid');
            if (! is_null($value)) {
                $record['value'] = $value;
            }
            yield $key => $record;

            if (filled($tree = data_get($entry, 't./'))) {
                yield from self::walkEntries($blockmap, $tree, $did);
            }
        }
    }

    /**
     * Get signed commit.
     *
     * Works only Bluesky/AtProto CAR.
     *
     * @return array{did: string, rev: string, sig: array{"$bytes": string}, data: array{"/": string}, prev: null, version: int}
     */
    public static function signedCommit(StreamInterface|string $data): array
    {
        $roots = CAR::decodeRoots($data);
        $root_cid = data_get($roots, 0);

        foreach (CAR::blockIterator($data) as $cid => $block) {
            if ($cid === $root_cid) {
                if (Arr::exists($block, 'sig')) {
                    return $block;
                }

                break;
            }
        }

        throw new RuntimeException('Signed commit not found.');
    }

    /**
     * ```
     * $signed = CAR::signedCommit($data);
     *
     * $pk = DidKey::parse('did key from DidDoc');
     *
     * if (CAR::verifySignedCommit($signed, $pk) {
     *
     * }
     * ```
     */
    public static function verifySignedCommit(array $signed, DidKey|string $publicKey): bool
    {
        $sig = data_get($signed, 'sig.$bytes');

        if (empty($sig)) {
            return false;
        }

        $sig = base64_decode($sig);

        $sig = Signature::fromCompact($sig);

        $unsigned = Arr::except($signed, 'sig');

        $cbor = CBOR::encode($unsigned);

        if ($publicKey instanceof DidKey) {
            $publicKey = $publicKey->key;
        }

        $pk = EC::loadPublicKey($publicKey);

        return $pk->verify($cbor, $sig);
    }
}
