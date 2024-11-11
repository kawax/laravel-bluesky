<?php

namespace Revolution\Bluesky\Types;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Types\AbstractBlob;
use Revolution\AtProto\Lexicon\Types\AbstractUnion;

final class SelfLabels extends AbstractUnion implements Arrayable
{
    protected array $labels;

    public function __construct(array $labels)
    {
        $this->type = 'com.atproto.label.defs#selfLabels';

        $this->labels = $labels;
    }

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