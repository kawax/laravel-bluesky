<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Notifications;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\Bluesky\Lexicon\Facet;

final class BlueskyMessage implements Arrayable
{
    use Conditionable;
    use Macroable;

    protected ?array $facets = null;
    protected ?array $embed = null;
    protected ?array $langs = null;

    public function __construct(
        protected string $text = '',
    ) {
    }

    public static function create(string $text = ''): self
    {
        return new self(text: $text);
    }

    /**
     * Append to existing text.
     */
    public function text(string $text): self
    {
        $this->text .= $text;

        return $this;
    }

    /**
     * Add new lines to text.
     */
    public function newLine(int $count = 1): self
    {
        $this->text .= Str::repeat(PHP_EOL, $count);

        return $this;
    }

    /**
     * Add mention facets.
     */
    public function mention(string $text, string $did): self
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
    public function link(string $text, string $uri): self
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
    public function tag(string $text, string $tag): self
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
    public function facet(array $facet): self
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
    public function embed(array|Arrayable $embed): self
    {
        $this->embed = $embed instanceof Arrayable ? $embed->toArray() : $embed;

        return $this;
    }

    /**
     * Add langs.
     */
    public function langs(array $langs): self
    {
        $this->langs = $langs;

        return $this;
    }

    /**
     * @return array{text: string, facets?: array, embed?: array, langs?: array}
     */
    public function toArray(): array
    {
        return collect(get_object_vars($this))
            ->reject(fn ($item) => is_null($item))
            ->toArray();
    }
}
