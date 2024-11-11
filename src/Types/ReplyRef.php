<?php

namespace Revolution\Bluesky\Types;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Attributes\NSID;

#[NSID('app.bsky.feed.post#replyRef')]
final readonly class ReplyRef implements Arrayable
{
    public function __construct(
        protected StrongRef $root,
        protected StrongRef $parent,
    ) {
    }

    public static function to(StrongRef $root, StrongRef $parent): self
    {
        return new self($root, $parent);
    }

    /**
     * @return array{root: array{uri: string, cid: string}, paret: array{uri: string, cid: string}}
     */
    public function toArray(): array
    {
        return [
            'root' => $this->root->toArray(),
            'parent' => $this->parent->toArray(),
        ];
    }
}
