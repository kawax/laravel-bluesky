<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Record;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Record\App\Bsky\Feed\AbstractPost;
use Revolution\Bluesky\Contracts\Recordable;
use Revolution\Bluesky\Types\ReplyRef;

final class Post extends AbstractPost implements Arrayable, Recordable
{
    use HasRecord;
    use Macroable;
    use Conditionable;

    public function __construct(string $text = '', ?array $facets = null)
    {
        $this->text = $text;
        $this->facets = $facets;
    }

    public static function create(string $text = '', ?array $facets = null): self
    {
        return new self($text, $facets);
    }

    public function text(string $text = ''): self
    {
        $this->text = $text;

        return $this;
    }

    public function facets(?array $facets = null): self
    {
        $this->facets = $facets;

        return $this;
    }

    public function embed(null|array|Arrayable $embed = null): self
    {
        $this->embed = $embed instanceof Arrayable ? $embed->toArray() : $embed;

        return $this;
    }

    public function langs(?array $langs = null): self
    {
        $this->langs = $langs;

        return $this;
    }

    public function reply(?ReplyRef $reply = null): self
    {
        $this->reply = $reply?->toArray();

        return $this;
    }

    public function createdAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
