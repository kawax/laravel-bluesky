<?php

namespace Revolution\Bluesky\RichText;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Enum\Facet;
use Revolution\Bluesky\Record\Post;

class TextBuilder implements Arrayable
{
    use Macroable;
    use Conditionable;

    public string $text = '';

    public array $facets = [];

    public function __construct(string $text = '')
    {
        $this->text = $text;
    }

    public static function make(string $text = ''): static
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
    public function mention(string $text, #[Format('did')] string $did): static
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
     * Add any facets.
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
     * @return array{text: string, facets: array}
     */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'facets' => $this->facets,
        ];
    }

    public function toPost(): Post
    {
        return Post::create($this->text, $this->facets);
    }
}
