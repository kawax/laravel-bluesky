<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use Illuminate\Support\Str;
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

    public function __construct(protected string $uri)
    {
        throw_unless(Str::startsWith($this->uri, self::ATP));

        throw_if(preg_match(self::ATP_URI_REGEX, $this->uri, $matches) === false);

        $this->protocol = $matches[1];
        $this->host = $matches[2];
        $this->pathname = $matches[3] ?? null;
        $this->searchParams = $matches[4] ?? null;
        $this->hash = $matches[5] ?? null;
    }

    /**
     * ```
     * $at = AtUri::parse('at://did:plc:+++/app.bsky.feed.post/+++');
     * $at->repo();
     * $at->collection();
     * $at->rkey();
     * ```
     */
    public static function parse(#[Format('at-uri')] string $uri): self
    {
        return new self($uri);
    }

    /**
     * ```
     * $at = AtUri::make(repo: 'did:plc:+++', collection: 'app.bsky.feed.post', rkey: '+++');
     * $uri = $at->toString();// 'at://did:plc:+++/app.bsky.feed.post/+++'
     * ```
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
     * ```
     * $at = AtUri::parse('at://did:plc:+++/app.bsky.feed.post/+++');
     * $uri = $at->toString();
     * $uri = (string) $at;
     * $uri = $at->__toString();
     * ```
     */
    public function toString(): string
    {
        return self::ATP.$this->host.$this->pathname.$this->searchParams.$this->hash;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
