<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Embed;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\Bluesky\Types\StrongRef;
use Revolution\AtProto\Lexicon\Union\App\Bsky\Embed\AbstractRecord;

final class QuoteRecord extends AbstractRecord implements Arrayable
{
    public function __construct(
        StrongRef $record,
    ) {
        $this->record = $record->toArray();
    }

    public static function create(StrongRef $record): self
    {
        return new self($record);
    }

    public function toArray(): array
    {
        return [
            '$type' => self::NSID,
            'record' => $this->record,
        ];
    }
}
