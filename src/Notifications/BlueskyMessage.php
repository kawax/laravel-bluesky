<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Notifications;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\Bluesky\Enums\Facet;

class BlueskyMessage implements Arrayable
{
    public ?array $facets = null;
    public ?array $embed = null;

    public function __construct(
        public string $text = '',
    ) {
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
        $byteStart = strlen($this->text);
        $byteEnd = $byteStart + strlen($text);

        return [
            'byteStart' => $byteStart,
            'byteEnd' => $byteEnd,
        ];
    }

    /**
     * Add embed.
     */
    public function embed(array $embed): static
    {
        $this->embed = $embed;

        return $this;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
