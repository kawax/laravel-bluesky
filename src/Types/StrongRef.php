<?php

namespace Revolution\Bluesky\Types;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Attributes\NSID;

#[NSID('com.atproto.repo.strongRef')]
final readonly class StrongRef implements Arrayable
{
    public function __construct(
        protected string $uri,
        protected string $cid,
    ) {
    }

    /**
     * @param  string  $uri  at://
     */
    public static function to(#[Format('at-uri')] string $uri, #[Format('cid')] string $cid): self
    {
        return new self($uri, $cid);
    }

    /**
     * @return array{uri: string, cid: string}
     */
    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'cid' => $this->cid,
        ];
    }
}
