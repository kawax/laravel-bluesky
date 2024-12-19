<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Core\CBOR;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CID;

/**
 * @internal
 *
 * @link https://github.com/mary-ext/atcute/blob/trunk/packages/utilities/cbor/lib/decode.ts
 */
final class Decoder
{
    protected const MAX_SIZE = 1024 * 1024 * 5;

    protected StreamInterface $stream;

    protected int $stream_size = 0;

    /**
     * @return array{0: mixed, 1: ?string}
     */
    public function decodeFirst(StreamInterface $stream): array
    {
        throw_unless($stream->isReadable());

        $this->stream = $stream;
        $this->stream_size = (int) min($stream->getSize(), self::MAX_SIZE);

        $value = $this->readValue();
        $remainder = $this->stream->getContents();

        return [CBOR::normalize($value), $remainder];
    }

    public function decode(StreamInterface $stream): mixed
    {
        [$value, $remainder] = $this->decodeFirst($stream);

        throw_unless(strlen($remainder ?? '') === 0);

        return $value;
    }

    private function readArgument(int $info): int
    {
        if ($info < 24) {
            return $info;
        }

        return match ($info) {
            24 => $this->readUint8(),
            25 => $this->readUint16(),
            26 => $this->readUint32(),
            27 => $this->readUint64(),
            default => throw new InvalidArgumentException(),
        };
    }

    private function readFloat64(): float
    {
        $data = $this->stream->read(8);
        $arr = unpack('E', $data);
        throw_unless($arr);

        return $arr[1];
    }

    private function readUint8(): int
    {
        return ord($this->stream->read(1));
    }

    private function readUint16(): int
    {
        $data = $this->stream->read(2);
        $arr = unpack('n', $data);
        throw_unless($arr);

        return $arr[1];
    }

    private function readUint32(): int
    {
        $data = $this->stream->read(4);
        $arr = unpack('N', $data);
        throw_unless($arr);

        return $arr[1];
    }

    private function readUint64(): int
    {
        $data = $this->stream->read(8);

        /** @var array{1: int, 2: int} $arr */
        $arr = unpack('N2', $data);
        throw_unless($arr);

        $hi = $arr[1];
        $lo = $arr[2];

        if ($hi > 0x1FFFFF) {
            throw new InvalidArgumentException();
        }

        return ($hi * (2 ** 32)) + $lo;
    }

    private function readString(int $length): string
    {
        return $this->stream->read(min($length, $this->stream_size));
    }

    private function readBytes(int $length): BytesWrapper
    {
        return new BytesWrapper($this->stream->read(min($length, $this->stream_size)));
    }

    private function readCid(int $length): CIDLinkWrapper
    {
        $cid = $this->stream->read(min($length, $this->stream_size));
        $cid = Str::ltrim($cid, CID::ZERO);

        return new CIDLinkWrapper($cid);
    }

    private function readValue(): mixed
    {
        $prelude = $this->readUint8();
        $type = $prelude >> 5;
        $info = $prelude & 0x1F;

        if ($type === 0) {
            return $this->readArgument($info);
        }

        if ($type === 1) {
            $value = $this->readArgument($info);

            return -1 - $value;
        }

        if ($type === 2) {
            return $this->readBytes($this->readArgument($info));
        }

        if ($type === 3) {
            return $this->readString($this->readArgument($info));
        }

        if ($type === 4) {
            $len = $this->readArgument($info);
            $arr = [];

            for ($i = 0; $i < $len; $i++) {
                $arr[] = $this->readValue();
            }

            return $arr;
        }

        if ($type === 5) {
            $len = $this->readArgument($info);
            $arr = [];

            for ($i = 0; $i < $len; $i++) {
                $key = $this->readValue();
                $arr[$key] = $this->readValue();
            }

            return $arr;
        }

        if ($type === 6) {
            $tag = $this->readArgument($info);

            throw_unless($tag === 42);

            $prelude = $this->readUint8();
            $type = $prelude >> 5;
            $info = $prelude & 0x1F;

            throw_unless($type === 2);

            $len = $this->readArgument($info);

            return $this->readCid($len);
        }

        if ($type === 7) {
            return match ($info) {
                20 => false,
                21 => true,
                22 => null,
                27 => $this->readFloat64(),
                default => throw new InvalidArgumentException(),
            };
        }

        throw new InvalidArgumentException();
    }
}
