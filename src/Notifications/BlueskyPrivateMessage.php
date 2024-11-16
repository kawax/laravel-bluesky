<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Notifications;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Attributes\NSID;
use Revolution\AtProto\Lexicon\Attributes\Ref;
use Revolution\AtProto\Lexicon\Attributes\Union;
use Revolution\Bluesky\RichText\TextBuilder;
use Revolution\Bluesky\Record\Post;

/**
 * Message for Chat / DM / Private Channel. Almost the same as {@link Post} but with less features.
 */
#[NSID('chat.bsky.convo.defs#messageInput')]
final class BlueskyPrivateMessage implements Arrayable
{
    use Macroable;
    use Conditionable;

    protected string $text;

    /**
     * Annotations of text (mentions, URLs, hashtags, etc).
     */
    #[Ref('app.bsky.richtext.facet')]
    protected ?array $facets = null;

    #[Union(['app.bsky.embed.record'])]
    protected ?array $embed = null;

    public function __construct(string $text = '', ?array $facets = null)
    {
        $this->text = $text;
        $this->facets = $facets;
    }

    /**
     * ```
     * use Revolution\Bluesky\Notifications\BlueskyPrivateMessage;
     *
     * $message = BlueskyPrivateMessage::create('test');
     * ```
     *
     * ```
     * use Revolution\Bluesky\Notifications\BlueskyPrivateMessage;
     * use Revolution\Bluesky\RichText\TextBuilder;
     *
     * $builder = TextBuilder::make(text: 'test')
     *                       ->newLine()
     *                       ->link(text: 'https://', uri: 'https://');
     *
     * $message = BlueskyPrivateMessage::create(text: $builder->text, facets: $builder->facets);
     * ```
     */
    public static function create(string $text = '', ?array $facets = null): self
    {
        return new self($text, $facets);
    }

    /**
     * ```
     * use Revolution\Bluesky\Notifications\BlueskyPrivateMessage;
     * use Revolution\Bluesky\RichText\TextBuilder;
     *
     *  $message = BlueskyPrivateMessage::build(function(TextBuilder $builder): TextBuilder {
     *      return $builder->text('test')
     *                     ->newLine()
     *                     ->tag(text: '#bluesky', tag: 'bluesky');
     *  });
     * ```
     *
     * @param  callable(TextBuilder $builder): TextBuilder  $callback
     */
    public static function build(callable $callback): self
    {
        $builder = TextBuilder::make();
        $builder = $callback($builder) ?? $builder;

        return new self($builder->text, $builder->facets);
    }

    /**
     * Unlike TextBuilder, it completely replaces text. You probably won't use this directly.
     */
    public function text(string $text = ''): self
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Specify facets directly.
     */
    public function facets(?array $facets = null): self
    {
        $this->facets = $facets;

        return $this;
    }

    /**
     * Unlike {@link Post}, only {@link QuoteRecord} Embed is supported.
     * ```
     * use Revolution\Bluesky\Embed\QuoteRecord;
     *
     * $quote = QuoteRecord::create(StrongRef::to(uri: 'at://', cid: 'cid'));
     *
     * $message->embed($quote);
     * ```
     */
    public function embed(null|array|Arrayable $embed = null): self
    {
        $this->embed = $embed instanceof Arrayable ? $embed->toArray() : $embed;

        return $this;
    }

    public function toArray(): array
    {
        return collect(get_object_vars($this))
            ->reject(fn ($item) => is_null($item))
            ->toArray();
    }
}
