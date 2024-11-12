<?php

namespace Revolution\Bluesky\Types;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Types\AbstractUnion;

use function Illuminate\Support\enum_value;

/**
 * Union object with only $type.
 *
 * ```
 * $blank = BlankUnion::make(type: '...')->toArray();
 * ```
 */
final class BlankUnion extends AbstractUnion implements Arrayable
{
    public function __construct(BackedEnum|string $type)
    {
        $this->type = enum_value($type);
    }

    public static function make(BackedEnum|string $type): self
    {
        return new self($type);
    }

    public function toArray(): array
    {
        return [
            '$type' => $this->type,
        ];
    }
}
