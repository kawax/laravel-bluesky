<?php

namespace Revolution\Bluesky\Support;

use Illuminate\Support\Str;

final class AtUri
{
    private const ATP_URI_REGEX = '/^(at:\/\/)?((?:did:[a-z0-9:%-]+)|(?:[a-z0-9][a-z0-9.:-]*))(\/[^?#\s]*)?(\?[^#\s]+)?(#[^\s]+)?$/i';

    protected ?string $host = null;
    protected ?string $pathname = null;

    public function __construct(protected string $uri)
    {
        preg_match(self::ATP_URI_REGEX, $this->uri, $matches);

        $this->host = $matches[2] ?? null;
        $this->pathname = $matches[3] ?? null;
    }

    public static function parse(string $uri): self
    {
        return new self($uri);
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
