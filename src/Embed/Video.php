<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Embed;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Enum\Embed;

final readonly class Video implements Arrayable
{
    public function __construct(
        private array $video,
        private ?string $alt = null,
        private ?array $captions = null,
        private ?array $aspectRatio = null,
    ) {
    }

    public static function create(array $video, ?string $alt = null, ?array $captions = null, ?array $aspectRatio = null): self
    {
        return new self(...func_get_args());
    }

    public function toArray(): array
    {
        return [
            '$type' => Embed::Video->value,
            'video' => collect([
                'video' => $this->video,
                'alt' => $this->alt,
                'captions' => $this->captions,
                'aspectRatio' => $this->aspectRatio,
            ])->reject(fn ($item) => is_null($item))
                ->toArray(),
        ];
    }
}
