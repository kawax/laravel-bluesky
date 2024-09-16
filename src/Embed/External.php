<?php

namespace Revolution\Bluesky\Embed;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\Bluesky\Enums\AtProto;

final readonly class External implements Arrayable
{
    public function __construct(
        private string $title,
        private string $description,
        private string $uri,
        private ?array $thumb = null,
    ) {
    }

    public static function create(string $title, string $description, string $uri, ?array $thumb = null): self
    {
        return new self(...func_get_args());
    }

    public function toArray(): array
    {
        return [
            '$type' => AtProto::External->value,
            'external' => collect([
                'uri' => $this->uri,
                'title' => $this->title,
                'description' => $this->description,
                'thumb' => $this->thumb,
            ])->reject(fn ($item) => is_null($item))
                ->toArray(),
        ];
    }
}
