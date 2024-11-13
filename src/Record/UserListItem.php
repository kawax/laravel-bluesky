<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Record;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Record\App\Bsky\Graph\AbstractListitem;
use Revolution\Bluesky\Contracts\Recordable;

final class UserListItem extends AbstractListitem implements Arrayable, Recordable
{
    use HasRecord;

    public function __construct(string $did, string $list)
    {
        $this->subject = $did;
        $this->list = $list;
    }

    public static function create(#[Format('did')] string $did, #[Format('at-uri')] string $list): self
    {
        return new self($did, $list);
    }
}
