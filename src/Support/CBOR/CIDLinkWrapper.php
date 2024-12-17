<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support\CBOR;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Revolution\Bluesky\Support\CID;
use Stringable;
use YOCLIB\Multiformats\Multibase\Multibase;

/**
 * @internal
 *
 * @link https://github.com/mary-ext/atcute/blob/trunk/packages/utilities/cbor/lib/cid-link.ts
 */
final readonly class CIDLinkWrapper implements Arrayable, Jsonable, Stringable
{
    public function __construct(protected string $bytes)
    {
    }

    public function cid(): string
    {
        if (str_starts_with($this->bytes, CID::V0_LEADING)) {
            return Multibase::encode(Multibase::BASE58BTC, $this->bytes, false);
        } else {
            return Multibase::encode(Multibase::BASE32, $this->bytes);
        }
    }

    public function bytes(): string
    {
        return $this->bytes;
    }

    public function toArray(): array
    {
        return ['$link' => $this->cid()];
    }

    public function mst(): array
    {
        return ['/' => $this->cid()];
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
