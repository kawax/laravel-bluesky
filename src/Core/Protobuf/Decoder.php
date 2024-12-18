<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Core\Protobuf;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Arr;
use Psr\Http\Message\StreamInterface;
use Revolution\Bluesky\Core\CID;
use Revolution\Bluesky\Core\Varint;
use RuntimeException;
use Throwable;
use YOCLIB\Multiformats\Multibase\Multibase;

/**
 * @internal
 *
 * @link https://github.com/ipld/js-dag-pb/blob/master/src/pb-decode.js
 */
final class Decoder
{
    /**
     * @throws Throwable
     */
    public function decode(StreamInterface $stream): array
    {
        $links = [];
        $linksBeforeData = false;
        $data = null;

        while ($stream->getSize() > $stream->tell()) {
            [$wireType, $fieldNum] = $this->decodeKey($stream);

            throw_unless($wireType === 2);
            throw_unless($fieldNum === 1 || $fieldNum === 2);

            if ($fieldNum === 1) {
                throw_unless(is_null($data));

                $data = $this->decodeBytes($stream);

                if (filled($links)) {
                    $linksBeforeData = true;
                }
            } elseif ($fieldNum === 2) {
                throw_if($linksBeforeData);

                $bytes = $this->decodeBytes($stream);
                $links[] = $this->decodeLink(Utils::streamFor($bytes));
            }
        }

        $node = [
            'Links' => $links,
        ];

        if (! is_null($data)) {
            $node['Data'] = $data;
        }

        return $node;
    }

    private function decodeKey(StreamInterface $stream): array
    {
        $varint = Varint::decodeStream($stream);

        $wireType = $varint & 0x7;
        $fieldNum = $varint >> 3;

        return [$wireType, $fieldNum];
    }

    private function decodeBytes(StreamInterface $stream): string
    {
        $varint = Varint::decodeStream($stream);

        return $stream->read($varint);
    }

    /**
     * @throws Throwable
     */
    private function decodeLink(StreamInterface $stream): array
    {
        $link = [];

        while ($stream->getSize() > $stream->tell()) {
            [$wireType, $fieldNum] = $this->decodeKey($stream);

            if ($fieldNum === 1) {
                throw_if(Arr::has($link, ['Hash', 'Name', 'Tsize']));
                throw_unless($wireType === 2);

                $bytes = $this->decodeBytes($stream);
                if (str_starts_with($bytes, CID::V0_LEADING)) {
                    // v0
                    $cid = Multibase::encode(Multibase::BASE58BTC, $bytes, false);
                } else {
                    // v1
                    $cid = Multibase::encode(Multibase::BASE32, $bytes);
                }
                $link['Hash'] = ['/' => $cid];
            } elseif ($fieldNum === 2) {
                throw_if(Arr::has($link, ['Name', 'Tsize']));
                throw_unless($wireType === 2);

                $link['Name'] = $this->decodeBytes($stream);
            } elseif ($fieldNum === 3) {
                throw_if(Arr::has($link, ['Tsize']));
                throw_unless($wireType === 0);

                $link['Tsize'] = Varint::decodeStream($stream);
            } else {
                throw new RuntimeException();
            }
        }

        return $link;
    }
}
