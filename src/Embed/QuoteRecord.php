<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Embed;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Enum\Embed;
use Revolution\AtProto\Lexicon\Types\AbstractUnion;
use Revolution\Bluesky\Types\StrongRef;

final class QuoteRecord extends AbstractUnion implements Arrayable
{
    public function __construct(
        private readonly StrongRef $record,
    ) {
        $this->type = Embed::Record->value;
    }

    public static function create(StrongRef $record): self
    {
        return new self($record);
    }

    public function toArray(): array
    {
        return [
            '$type' => $this->type,
            'record' => $this->record->toArray(),
        ];
    }
}
