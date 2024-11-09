<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Record;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Record\App\Bsky\Graph\AbstractBlock;
use Revolution\Bluesky\Contracts\Recordable;

class Block extends AbstractBlock implements Arrayable, Recordable
{
    use HasRecord;

    public function __construct(string $did)
    {
        $this->subject = $did;
    }

    public static function create(string $did): static
    {
        return new static($did);
    }
}
