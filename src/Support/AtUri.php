<?php

namespace Revolution\Bluesky\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Revolution\AtProto\Lexicon\Attributes\Format;

/**
 * @link https://github.com/bluesky-social/atproto/blob/main/packages/syntax/src/aturi.ts
 */
final readonly class AtUri
{
    public const ATP = 'at://';

    protected const ATP_URI_REGEX = '/^(at:\/\/)?((?:did:[a-z0-9:%-]+)|(?:[a-z0-9][a-z0-9.:-]*))(\/[^?#\s]*)?(\?[^#\s]+)?(#[^\s]+)?$/i';

    protected ?string $protocol;
    protected ?string $host;
    protected ?string $pathname;

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
    }

    public static function parse(#[Format('at-uri')] string $uri): self
    {
        return new self($uri);
    }

    public function protocol(): string
    {
        return $this->protocol ?? '';
    }

    public function repo(): string
    {
        return $this->host ?? '';
    }

    public function collection(): string
    {
        return Str::of($this->pathname)->explode('/')->get(1, default: '');
    }

    public function rkey(): string
    {
        return Str::of($this->pathname)->explode('/')->get(2, default: '');
    }
}
