<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Revolution\Bluesky\Core\CBOR\AtBytes;

readonly class SignedLabel extends UnsignedLabel
{
    public function __construct(
        UnsignedLabel $unsigned,
        public AtBytes $sig,
        public int $ver = Labeler::VERSION,
    ) {
        parent::__construct(
            ...$unsigned->toArray(),
        );
    }

    public static function fromArray(array $array): self
    {
        if (! Arr::exists($array, 'sig')) {
            throw new InvalidArgumentException('sig is required');
        }

        $sig = Arr::get($array, 'sig');

        if (is_array($sig)) {
            $sig = AtBytes::fromArray($sig);
        }

        if (is_string($sig)) {
            $sig = new AtBytes($sig);
        }

        return new self(
            UnsignedLabel::fromArray($array),
            $sig,
        );
    }

    public function toUnsigned(): UnsignedLabel
    {
        return new UnsignedLabel(...Arr::except($this->toArray(), ['sig', 'ver']));
    }

    /**
     * @return array{uri: string, cid: ?string, val: string, src: string, cts: string, exp: ?string, neg: bool, sig: AtBytes}
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
