<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CBOR\AtBytes;

class SavedLabel extends SignedLabel
{
    public function __construct(
        public int $id,
        SignedLabel $signed,
    ) {
        parent::__construct(
            $signed->toUnsigned(),
            $signed->sig,
        );
    }

    public static function fromArray(array $array): self
    {
        if (! Arr::hasAny($array, ['id', 'uri', 'val', 'src', 'cts', 'sig'])) {
            throw new InvalidArgumentException();
        }

        $unsigned = UnsignedLabel::fromArray($array);

        $sig = Arr::get($array, 'sig');

        if (is_array($sig)) {
            $sig = AtBytes::fromArray($sig);
        }

        if (is_string($sig)) {
            $sig = new AtBytes($sig);
        }

        return new self(
            Arr::get($array, 'id', 0),
            new SignedLabel($unsigned, $sig),
        );
    }

    /**
     * @return array{id: int, uri: string, cid: ?string, val: string, src: string, cts: string, exp: ?string, neg: bool, sig: AtBytes}
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function toEmit(): array
    {
        return collect($this->toArray())
            ->except('id')
            ->reject(fn ($value) => is_null($value))
            ->reject(fn ($value, $key) => $key === 'neg' && $value === false)
            ->sortKeysUsing(new CBOR\MapKeySort())
            ->toArray();
    }

    public function toBytes(): string
    {
        $seq = $this->id;

        $header = ['op' => 1, 't' => '#labels'];

        $body = [
            'seq' => $seq,
            'labels' => [$this->toEmit()],
        ];

        return CBOR::encode($header).CBOR::encode($body);
    }
}
