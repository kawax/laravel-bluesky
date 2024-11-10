<?php

namespace Revolution\Bluesky\Support;

use Illuminate\Contracts\Support\Arrayable;

final readonly class StrongRef implements Arrayable
{
    public function __construct(
        protected string $uri,
        protected string $cid,
    ) {
    }

    public static function to(string $uri, string $cid): self
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
