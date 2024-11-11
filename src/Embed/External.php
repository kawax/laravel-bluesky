<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Embed;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Enum\Embed;
use Revolution\AtProto\Lexicon\Types\AbstractUnion;
use Revolution\Bluesky\Types\Blob;

final class External extends AbstractUnion implements Arrayable
{
    public function __construct(
        private readonly string $title,
        private readonly string $description,
        private readonly string $uri,
        private readonly null|array|Blob $thumb = null,
    ) {
        $this->type = Embed::External->value;
    }

    public static function create(string $title, string $description, string $uri, ?string $thumb = null): self
    {
        return new self(...func_get_args());
    }

    public function toArray(): array
    {
        $thumb = $this->thumb instanceof Blob ? $this->thumb->toArray() : $this->thumb;

        return [
            '$type' => $this->type,
            'external' => collect([
                'uri' => $this->uri,
                'title' => $this->title,
                'description' => $this->description,
                'thumb' => $thumb,
            ])->reject(fn ($item) => is_null($item))
                ->toArray(),
        ];
    }
}
