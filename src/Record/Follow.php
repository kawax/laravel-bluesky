<?php

namespace Revolution\Bluesky\Record;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Record\App\Bsky\Graph\AbstractFollow;
use Revolution\Bluesky\Contracts\Recordable;

class Follow extends AbstractFollow implements Arrayable, Recordable
{
    use HasRecord;

    public function __construct(string $did)
    {
        $this->subject = $did;
    }

    public static function create(string $did): self
    {
        return new self($did);
    }
}
