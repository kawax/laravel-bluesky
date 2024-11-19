<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Embed;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\Bluesky\Types\StrongRef;
use Revolution\AtProto\Lexicon\Union\App\Bsky\Embed\AbstractRecord;

final class QuoteRecord extends AbstractRecord implements Arrayable
{
    public function __construct(
        private readonly StrongRef $quote,
    ) {
    }

    public static function create(StrongRef $quote): self
    {
        return new self($quote);
    }

    public function toArray(): array
    {
        return [
            '$type' => self::NSID,
            'record' => $this->quote->toArray(),
        ];
    }
}
