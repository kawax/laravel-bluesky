<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Core\CBOR;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\Bluesky\Core\CID;
use YOCLIB\Multiformats\Multibase\Multibase;

/**
 * @internal
 *
 * @link https://github.com/mary-ext/atcute/blob/trunk/packages/utilities/cbor/lib/cid-link.ts
 */
final readonly class CIDLinkWrapper implements Arrayable
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

    public function link(): array
    {
        return ['$link' => $this->cid()];
    }

    public function mst(): array
    {
        return ['/' => $this->cid()];
    }

    public function toArray(): array
    {
        return $this->mst();
    }
}
