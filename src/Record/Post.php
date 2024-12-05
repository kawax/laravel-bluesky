<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Record;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Record\App\Bsky\Feed\AbstractPost;
use Revolution\Bluesky\Contracts\Recordable;
use Revolution\Bluesky\RichText\TextBuilder;
use Revolution\Bluesky\Types\ReplyRef;
use Revolution\Bluesky\Types\SelfLabels;

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

    /**
     * ```
     * use Revolution\Bluesky\Record\Post;
     *
     * $post = Post::create('test');
     * ```
     *
     * ```
     * use Revolution\Bluesky\Record\Post;
     * use Revolution\Bluesky\RichText\TextBuilder;
     *
     * $builder = TextBuilder::make(text: 'test')
     *                       ->newLine()
     *                       ->link(text: 'https://');
     *
     * $post = Post::create(text: $builder->text, facets: $builder->facets);
     * ```
     */
    public static function create(string $text = '', ?array $facets = null): self
    {
        return new self($text, $facets);
    }

    /**
     * ```
     * use Revolution\Bluesky\Record\Post;
     * use Revolution\Bluesky\RichText\TextBuilder;
     *
     *  $post = Post::build(function (TextBuilder $builder) {
     *      $builder->text('test')
     *              ->newLine()
     *              ->tag(text: '#bluesky');
     *  });
     * ```
     *
     * @param  callable(TextBuilder $builder): TextBuilder  $callback
     */
    public static function build(callable $callback): self
    {
        $builder = TextBuilder::make()->tap($callback);

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
     * ```
     * use Revolution\Bluesky\Embed\External;
     *
     * $external = External::create(
     *     title: 'Title',
     *     description: 'description',
     *     uri: 'https://',
     * );
     *
     * $post->embed($external);
     * ```
     */
    public function embed(null|array|Arrayable $embed = null): self
    {
        $this->embed = $embed instanceof Arrayable ? $embed->toArray() : $embed;

        return $this;
    }

    /**
     * ```
     * $post->langs(['en-US']);
     * ```
     */
    public function langs(?array $langs = null): self
    {
        $this->langs = $langs;

        return $this;
    }

    /**
     * ```
     * use Revolution\Bluesky\Types\ReplyRef;
     * use Revolution\Bluesky\Types\StrongRef;
     *
     * $reply = ReplyRef::to(root: StrongRef::to(uri: 'at://', cid: 'cid'), parent: StrongRef::to(uri: 'at://', cid: 'cid'));
     *
     * $post = Post::create('test')->reply($reply);
     * ```
     */
    public function reply(?ReplyRef $reply = null): self
    {
        $this->reply = $reply?->toArray();

        return $this;
    }

    public function labels(?SelfLabels $labels = null): self
    {
        $this->labels = $labels?->toArray();

        return $this;
    }

    /**
     * Even if you don't use this, the current date and time will be automatically specified when posting. Use this when you want to import past posts.
     *
     * ```
     * $post->createdAt('2024-12-31T12:00:00.000Z');
     * ```
     */
    public function createdAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
