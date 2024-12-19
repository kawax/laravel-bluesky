<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Core\CBOR;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Revolution\Bluesky\Core\CID;

/**
 * @internal
 *
 * @link https://github.com/mary-ext/atcute/blob/trunk/packages/utilities/cbor/lib/encode.ts
 */
final class Encoder
{
    private const CHUNK_SIZE = 1024;

    private array $chunks = [];

    private string $buffer = '';

    private int $pos = 0;

    public function encode(mixed $value): string
    {
        $this->createState();
        $this->writeValue($value);
        $this->flushBuffer();

        return implode('', $this->chunks);
    }

    private function createState(): void
    {
        $this->chunks = [];
        $this->buffer = str_repeat(CID::ZERO, self::CHUNK_SIZE);
        $this->pos = 0;
    }

    private function flushBuffer(): void
    {
        if ($this->pos > 0) {
            $this->chunks[] = substr($this->buffer, 0, $this->pos);
        }
    }

    private function resizeIfNeeded(int $needed): void
    {
        $bufLen = strlen($this->buffer);
        if ($bufLen < $this->pos + $needed) {
            if ($this->pos > 0) {
                $this->chunks[] = substr($this->buffer, 0, $this->pos);
            }

            $newSize = max(self::CHUNK_SIZE, $needed);
            $this->buffer = str_repeat(CID::ZERO, $newSize);
            $this->pos = 0;
        }
    }

    private function getInfo(int $arg): int
    {
        return match (true) {
            $arg < 24 => $arg,
            $arg < 0x100 => 24,
            $arg < 0x10000 => 25,
            $arg < 0x100000000 => 26,
            default => 27,
        };
    }

    private function writeFloat64(float $val): void
    {
        $this->resizeIfNeeded(8);
        $data = pack('E', $val);
        $this->writeRaw($data);
    }

    private function writeUint8(int $val): void
    {
        $this->resizeIfNeeded(1);
        $this->buffer[$this->pos] = chr($val & 0xFF);
        $this->pos += 1;
    }

    private function writeUint16(int $val): void
    {
        $this->resizeIfNeeded(2);
        $data = pack('n', $val);
        $this->writeRaw($data);
    }

    private function writeUint32(int $val): void
    {
        $this->resizeIfNeeded(4);
        $data = pack('N', $val);
        $this->writeRaw($data);
    }

    private function writeUint64(int $val): void
    {
        $hi = ($val >> 32) & 0xFFFFFFFF;
        $lo = $val & 0xFFFFFFFF;

        $this->resizeIfNeeded(8);
        $data = pack('N2', $hi, $lo);
        $this->writeRaw($data);
    }

    private function writeTypeAndArgument(int $type, int $arg): void
    {
        $info = $this->getInfo($arg);
        $this->writeUint8(($type << 5) | $info);

        switch ($info) {
            case 24:
                $this->writeUint8($arg);
                break;
            case 25:
                $this->writeUint16($arg);
                break;
            case 26:
                $this->writeUint32($arg);
                break;
            case 27:
                $this->writeUint64($arg);
                break;
        }
    }

    private function writeInteger(int $val): void
    {
        if ($val < 0) {
            $this->writeTypeAndArgument(1, -$val - 1);
        } else {
            $this->writeTypeAndArgument(0, $val);
        }
    }

    private function writeFloat(float $val): void
    {
        $this->writeUint8(0xFB);
        $this->writeFloat64($val);
    }

    private function writeNumber(int|float $val): void
    {
        if (is_nan($val)) {
            throw new InvalidArgumentException();
        }

        if ($val > PHP_INT_MAX || $val < -PHP_INT_MAX) {
            throw new InvalidArgumentException();
        }

        if (is_int($val)) {
            $this->writeInteger($val);
        } else {
            $this->writeFloat((float) $val);
        }
    }

    private function writeString(string $val): void
    {
        $len = strlen($val);

        $this->writeTypeAndArgument(3, $len);
        $this->resizeIfNeeded($len);
        $this->writeRaw($val);
    }

    private function writeBytes(string $val): void
    {
        $bytes = base64_decode($val);
        $len = strlen($bytes);

        $this->writeTypeAndArgument(2, $len);
        $this->resizeIfNeeded($len);
        $this->writeRaw($bytes);
    }

    private function writeCid(string $val): void
    {
        $buf = CID::decodeBytes($val);
        $len = strlen($buf) + 1;

        $this->writeTypeAndArgument(6, 42);
        $this->writeTypeAndArgument(2, $len);

        $this->resizeIfNeeded($len);
        $this->buffer[$this->pos] = CID::ZERO;
        $this->pos += 1;
        $this->writeRaw($buf);
    }

    private function writeValue(mixed $val): void
    {
        if ($val === null) {
            $this->writeUint8(0xF6);

            return;
        }

        if ($val === false) {
            $this->writeUint8(0xF4);

            return;
        }

        if ($val === true) {
            $this->writeUint8(0xF5);

            return;
        }

        if (is_float($val) || is_int($val)) {
            $this->writeNumber($val);

            return;
        }

        if (is_string($val)) {
            $this->writeString($val);

            return;
        }

        if ($val instanceof Arrayable) {
            $val = $val->toArray();
        }

        if (is_array($val) && Arr::isList($val)) {
            $len = count($val);
            $this->writeTypeAndArgument(4, $len);
            foreach ($val as $v) {
                $this->writeValue($v);
            }

            return;
        }

        if (is_object($val)) {
            $val = get_object_vars($val);
        }

        if (Arr::isAssoc($val)) {
            if (Arr::exists($val, '$link')) {
                $this->writeCid(data_get($val, '$link'));

                return;
            }

            if (Arr::exists($val, '/')) {
                $this->writeCid(data_get($val, '/'));

                return;
            }

            if (Arr::exists($val, '$bytes')) {
                $this->writeBytes(data_get($val, '$bytes'));

                return;
            }

            $filtered = collect((array) $val)
                ->reject(fn ($val, $key) => $key !== 'prev' && is_null($val))
                ->toArray();

            $keys = array_keys($filtered);
            usort($keys, [$this, 'compareKeys']);

            $len = count($keys);
            $this->writeTypeAndArgument(5, $len);

            foreach ($keys as $key) {
                $this->writeString($key);
                $this->writeValue($filtered[$key]);
            }

            return;
        }

        throw new InvalidArgumentException();
    }

    private function compareKeys(string $a, string $b): int
    {
        if (strlen($a) < strlen($b)) {
            return -1;
        } elseif (strlen($b) < strlen($a)) {
            return 1;
        } else {
            return ($a < $b) ? -1 : 1;
        }
    }

    private function writeRaw(string $data): void
    {
        $len = strlen($data);

        for ($i = 0; $i < $len; $i++) {
            $this->buffer[$this->pos++] = $data[$i];
        }
    }
}
