<?php

namespace Revolution\Bluesky\RichText;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Attributes\Ref;
use Revolution\AtProto\Lexicon\Enum\Facet;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Post;

final class TextBuilder implements Arrayable
{
    use Macroable;
    use Conditionable;
    use Tappable;

    public string $text = '';

    /**
     * Annotations of text (mentions, URLs, hashtags, etc).
     */
    #[Ref('app.bsky.richtext.facet')]
    public array $facets = [];

    public function __construct(string $text = '')
    {
        $this->text = $text;
    }

    public static function make(string $text = ''): self
    {
        return new self(text: $text);
    }

    /**
     * Auto-detect facets. If you want to be sure it links you're better off building it manually.
     *
     * ```
     * $builder = TextBuilder::make(text: '[at]alice.test test https://example.com #alice')->detectFacets();
     *
     * $post = Post::create($builder->text, $builder->facets);
     * ```
     * ```
     * $post = Post::build(function (TextBuilder $builder) {
     *     $builder->text('[at]alice.test test https://example.com #alice')
     *             ->detectFacets()
     *             ->newLine()
     *             ->tag('#bob', 'bob');// Add more facets after detectFacets().
     * });
     * ```
     * ([at] should be written as @)
     */
    public function detectFacets(): self
    {
        $this->facets = app()->call(DetectFacets::class, ['text' => $this->text]);

        return $this;
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
        $this->text .= Str::repeat(PHP_EOL, max($count, 1));

        return $this;
    }

    /**
     * Add mention facets.
     *
     * ```
     * $builder->mention(text: '@***.bsky.social', did: 'did:plc:***');
     * ```
     * If did is not passed, did will be automatically resolved from handle.
     * ```
     * $builder->mention(text: '@***.bsky.social');
     * ```
     */
    public function mention(string $text, #[Format('did')] ?string $did = null): self
    {
        $text = Str::rtrim($text);

        if (is_null($did) && Str::startsWith($text, '@')) {
            $handle = Str::of($text)->after('@')->trim()->toString();
            $did = Bluesky::resolveHandle($handle)->json('did');
        }

        if (filled($did)) {
            $this->facets[] = [
                'index' => $this->buildFacetIndex($text),
                'features' => [
                    [
                        '$type' => Facet::Mention->value,
                        'did' => $did,
                    ],
                ],
            ];
        }

        $this->text .= $text;

        return $this;
    }

    /**
     * Add link facets.
     *
     * ```
     *  $builder->link(text: 'https://example.com', uri: 'https://example.com');
     *  ```
     *  If uri is not passed, input text will be used as the uri.
     *  ```
     *  $builder->link(text: 'https://example.com');
     *  ```
     */
    public function link(string $text, ?string $uri = null): self
    {
        $text = Str::rtrim($text);

        if (is_null($uri)) {
            $uri = $text;
        }

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
     *
     * ```
     * $builder->tag(text: '#alice', tag: 'alice');
     * ```
     * If tag is not passed, tag will be automatically set from input text.
     * ```
     * $builder->tag(text: '#alice');
     * ```
     */
    public function tag(string $text, ?string $tag = null): self
    {
        $text = Str::rtrim($text);

        if (is_null($tag) && Str::startsWith($text, '#')) {
            $tag = Str::of($text)->after('#')->trim()->toString();
        }

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
    public function facet(array $facet): self
    {
        $this->facets[] = $facet;

        return $this;
    }

    /**
     * Remove all Facets.
     */
    public function resetFacets(): self
    {
        $this->facets = [];

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
