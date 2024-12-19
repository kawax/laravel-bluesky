<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Types;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Attributes\NSID;
use Revolution\AtProto\Lexicon\Types\AbstractUnion;

#[NSID('com.atproto.label.defs#selfLabels')]
final class SelfLabels extends AbstractUnion implements Arrayable
{
    /**
     * @param  array<string>  $labels
     */
    public function __construct(protected array $labels)
    {
        $this->type = 'com.atproto.label.defs#selfLabels';
    }

    /**
     * @param  array<string>  $labels
     */
    public static function make(array $labels): self
    {
        return new self($labels);
    }

    public function toArray(): array
    {
        return [
            '$type' => $this->type,
            'values' => collect($this->labels)->map(fn ($label) => ['val' => $label]),
        ];
    }
}
