<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Record;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Record\App\Bsky\Feed\AbstractLike;
use Revolution\Bluesky\Contracts\Recordable;
use Revolution\Bluesky\Types\StrongRef;

final class Like extends AbstractLike implements Arrayable, Recordable
{
    use HasRecord;

    public function __construct(StrongRef $subject)
    {
        $this->subject = $subject->toArray();
    }

    public static function create(StrongRef $subject): self
    {
        return new self($subject);
    }
}
