<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support\CBOR;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Stringable;

/**
 * @internal
 *
 * @link https://github.com/mary-ext/atcute/blob/trunk/packages/utilities/cbor/lib/bytes.ts
 */
final readonly class BytesWrapper implements Arrayable, Jsonable, Stringable
{
    public function __construct(protected string $bytes)
    {
    }

    public function bytes(): string
    {
        return $this->bytes;
    }

    public function encode(): string
    {
        return base64_encode($this->bytes);
    }

    public function toArray(): array
    {
        return ['$bytes' => $this->encode()];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
