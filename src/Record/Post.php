<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Record;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Enum\Facet;
use Revolution\AtProto\Lexicon\Record\App\Bsky\Feed\AbstractPost;
use Revolution\Bluesky\Contracts\Recordable;

class Post extends AbstractPost implements Arrayable, Recordable
{
    use HasRecord;
    use Macroable;
    use Conditionable;

    public function __construct(string $text = '')
    {
        $this->text = $text;
    }

    public static function create(string $text = ''): static
    {
        return new static(text: $text);
    }

    /**
     * Append to existing text.
     */
    public function text(string $text): static
    {
        $this->text .= $text;

        return $this;
    }

    /**
     * Add new lines to text.
     */
    public function newLine(int $count = 1): static
    {
        $this->text .= Str::repeat(PHP_EOL, $count);

        return $this;
    }

    /**
     * Add mention facets.
     */
    public function mention(string $text, string $did): static
    {
        $this->facets[] = [
            'index' => $this->buildFacetIndex($text),
            'features' => [
                [
                    '$type' => Facet::Mention->value,
                    'did' => $did,
                ],
            ],
        ];

        $this->text .= $text;

        return $this;
    }

    /**
     * Add link facets.
     */
    public function link(string $text, string $uri): static
    {
        $this->facets[] = [
            'index' => $this->buildFacetIndex($text),
            'features' => [
                [
                    '$type' => Facet::Link->value,
                    'uri' => $uri,
                ],
            ],
        ];

        $this->text .= $text;

        return $this;
    }

    /**
     * Add tag facets.
     */
    public function tag(string $text, string $tag): static
    {
        $this->facets[] = [
            'index' => $this->buildFacetIndex($text),
            'features' => [
                [
                    '$type' => Facet::Tag->value,
                    'tag' => $tag,
                ],
            ],
        ];

        $this->text .= $text;

        return $this;
    }

    /**
     * Add facet.
     */
    public function facet(array $facet): static
    {
        $this->facets[] = $facet;

        return $this;
    }

    private function buildFacetIndex(string $text): array
    {
        return [
            'byteStart' => $byteStart = strlen($this->text),
            'byteEnd' => $byteStart + strlen($text),
        ];
    }

    /**
     * Add embed.
     */
    public function embed(array|Arrayable $embed): static
    {
        $this->embed = $embed instanceof Arrayable ? $embed->toArray() : $embed;

        return $this;
    }

    /**
     * Add langs.
     */
    public function langs(array $langs): static
    {
        $this->langs = $langs;

        return $this;
    }
}
