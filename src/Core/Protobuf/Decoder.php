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
        throw_unless($stream->isReadable() && $stream->isSeekable());

        $links = [];
        $linksBeforeData = false;
        $data = null;

        $size = $stream->getSize();

        while ($size > $stream->tell()) {
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

    private function decodeLink(StreamInterface $stream): array
    {
        $link = [];
        $size = $stream->getSize();

        while ($size > $stream->tell()) {
            [$wireType, $fieldNum] = $this->decodeKey($stream);

            switch ($fieldNum) {
                case 1:
                    $this->validateLink($link, ['Hash', 'Name', 'Tsize']);
                    $this->validateWireType($wireType, 2);
                    $link['Hash'] = $this->decodeHash($stream);
                    break;
                case 2:
                    $this->validateLink($link, ['Name', 'Tsize']);
                    $this->validateWireType($wireType, 2);
                    $link['Name'] = $this->decodeBytes($stream);
                    break;
                case 3:
                    $this->validateLink($link, ['Tsize']);
                    $this->validateWireType($wireType, 0);
                    $link['Tsize'] = Varint::decodeStream($stream);
                    break;
                default:
                    throw new RuntimeException('Invalid field number');
            }
        }

        return $link;
    }

    private function validateLink(array $link, array $fields): void
    {
        foreach ($fields as $field) {
            if (Arr::has($link, $field)) {
                throw new RuntimeException("Field $field already exists in link");
            }
        }
    }

    private function validateWireType(int $wireType, int $expectedType): void
    {
        if ($wireType !== $expectedType) {
            throw new RuntimeException("Invalid wire type: expected $expectedType, got $wireType");
        }
    }

    private function decodeHash(StreamInterface $stream): array
    {
        $bytes = $this->decodeBytes($stream);

        $cid = CID::encodeBytes($bytes);

        return ['/' => $cid];
    }
}
