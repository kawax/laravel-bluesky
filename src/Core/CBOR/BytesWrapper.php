<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Core\CBOR;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @internal
 *
 * @link https://github.com/mary-ext/atcute/blob/trunk/packages/utilities/cbor/lib/bytes.ts
 */
final readonly class BytesWrapper implements Arrayable
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
}
