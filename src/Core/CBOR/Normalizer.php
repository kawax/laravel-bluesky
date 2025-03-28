<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Core\CBOR;

/**
 * Match the official go-repo-export output.
 *
 * @link https://github.com/bluesky-social/cookbook/tree/main/go-repo-export
 *
 * @todo
 */
final class Normalizer
{
    public function __invoke(mixed $data): mixed
    {
        if (is_array($data)) {
            return collect($data)->map(function ($item, $key) {
                if (in_array($key, ['ref', 'link'], true) && $item instanceof CIDLinkWrapper) {
                    return $item->link();
                }

                if (in_array($key, ['v', 't', 'l', 'data'], true) && $item instanceof CIDLinkWrapper) {
                    return $item->mst();
                }

                if ($item instanceof CIDLinkWrapper) {
                    return $item->cid();
                }

                // Only here is in ['$bytes' => 'base64'] format
                if ($key === 'sig' && $item instanceof AtBytes) {
                    return $item->toArray();
                }

                if ($item instanceof AtBytes) {
                    return $item->toBytes();
                }

                return $this($item);
            })->toArray();
        }

        return $data;
    }
}
