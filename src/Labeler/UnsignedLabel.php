<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use InvalidArgumentException;

readonly class UnsignedLabel implements Arrayable
{
    public function __construct(
        public string $uri,
        public ?string $cid,
        public string $val,
        public string $src,
        public string $cts,
        public ?string $exp,
        public bool $neg = false,
    ) {
    }

    public static function fromArray(array $array): self
    {
        if (! Arr::hasAny($array, ['uri', 'val', 'src', 'cts'])) {
            throw new InvalidArgumentException('uri, val, src, cts are required');
        }

        return new self(
            data_get($array, 'uri', ''),
            data_get($array, 'cid'),
            data_get($array, 'val', ''),
            data_get($array, 'src', ''),
            data_get($array, 'cts', ''),
            data_get($array, 'exp'),
            data_get($array, 'neg', false),
        );
    }

    /**
     * @return array{uri: string, cid: ?string, val: string, src: string, cts: string, exp: ?string, neg: bool}
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
