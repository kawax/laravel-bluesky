<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Record;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Record\App\Bsky\Graph\AbstractList;
use Revolution\Bluesky\Contracts\Recordable;

class UserList extends AbstractList implements Arrayable, Recordable
{
    use HasRecord;

    public function __construct(string $name, string $purpose, string $description)
    {
        $this->name = $name;
        $this->purpose = $purpose;
        $this->description = $description;
    }

    public static function create(string $name, string $purpose, string $description): static
    {
        return new static($name, $purpose, $description);
    }
}
