<?php

namespace Revolution\Bluesky\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * https://github.com/bluesky-social/atproto/blob/main/packages/syntax/src/aturi.ts
 */
final class AtUri
{
    public const ATP = 'at://';

    protected const ATP_URI_REGEX = '/^(at:\/\/)?((?:did:[a-z0-9:%-]+)|(?:[a-z0-9][a-z0-9.:-]*))(\/[^?#\s]*)?(\?[^#\s]+)?(#[^\s]+)?$/i';

    protected ?string $protocol = null;
    protected ?string $host = null;
    protected ?string $pathname = null;

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

    public static function parse(string $uri): self
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
