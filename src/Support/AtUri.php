<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Stringable;

/**
 * @link https://github.com/bluesky-social/atproto/blob/main/packages/syntax/src/aturi.ts
 */
final readonly class AtUri implements Stringable
{
    protected const ATP = 'at://';

    protected const ATP_URI_REGEX = '/^(at:\/\/)?((?:did:[a-z0-9:%-]+)|(?:[a-z0-9][a-z0-9.:-]*))(\/[^?#\s]*)?(\?[^#\s]+)?(#[^\s]+)?$/i';

    protected ?string $protocol;
    protected ?string $host;
    protected ?string $pathname;
    protected ?string $searchParams;
    protected ?string $hash;

    /**
     * @throw InvalidArgumentException
     */
    public function __construct(protected string $uri)
    {
        if (! Str::startsWith($this->uri, self::ATP)) {
            throw new InvalidArgumentException();
        }

        if (preg_match(self::ATP_URI_REGEX, $this->uri, $matches) === false) {
            throw new InvalidArgumentException();
        }

        $this->protocol = $matches[1] ?? null;
        $this->host = $matches[2] ?? null;
        $this->pathname = $matches[3] ?? null;
        $this->searchParams = $matches[4] ?? null;
        $this->hash = $matches[5] ?? null;
    }

    /**
     * parse.
     *
     * ```
     * $at = AtUri::parse('at://did:plc:+++/app.bsky.feed.post/+++');
     * $at->repo();
     * $at->collection();
     * $at->rkey();
     * ```
     *
     * @throw InvalidArgumentException
     */
    public static function parse(#[Format('at-uri')] string $uri): self
    {
        return new self($uri);
    }

    /**
     * make.
     *
     * ```
     * $at = AtUri::make(repo: 'did:plc:+++', collection: 'app.bsky.feed.post', rkey: '+++');
     * $uri = $at->__toString();// 'at://did:plc:+++/app.bsky.feed.post/+++'
     * ```
     *
     * @throw InvalidArgumentException
     */
    public static function make(string $repo, ?string $collection = null, ?string $rkey = null): self
    {
        return new self(self::ATP.collect(func_get_args())->implode('/'));
    }

    public function protocol(): string
    {
        return $this->protocol ?? '';
    }

    /**
     * @return string DID
     */
    public function repo(): string
    {
        return $this->host ?? '';
    }

    /**
     * @return string `app.bsky.feed.post`
     */
    public function collection(): string
    {
        return Str::of($this->pathname)->explode('/')->get(1, default: '');
    }

    /**
     * @return string Record key
     */
    public function rkey(): string
    {
        return Str::of($this->pathname)->explode('/')->get(2, default: '');
    }

    /**
     * toString.
     *
     * ```
     * $at = AtUri::parse('at://did:plc:+++/app.bsky.feed.post/+++');
     * $uri = (string) $at;
     * $uri = $at->__toString();
     * ```
     */
    public function __toString(): string
    {
        return self::ATP.$this->host.$this->pathname.$this->searchParams.$this->hash;
    }
}
