<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Embed;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Enum\Embed;
use Revolution\AtProto\Lexicon\Types\AbstractUnion;
use Revolution\Bluesky\Types\BlobRef;

final class Video extends AbstractUnion implements Arrayable
{
    public function __construct(
        private readonly BlobRef|array $video,
        private readonly ?string $alt = null,
        private readonly ?array $captions = null,
        private readonly ?array $aspectRatio = null,
    ) {
        $this->type = Embed::Video->value;
    }

    public static function create(BlobRef|array $video, ?string $alt = null, ?array $captions = null, ?array $aspectRatio = null): self
    {
        return new self(...func_get_args());
    }

    public function toArray(): array
    {
        return [
            '$type' => $this->type,
            'video' => collect([
                'video' => $this->video instanceof BlobRef ? $this->video->toArray() : $this->video,
                'alt' => $this->alt,
                'captions' => $this->captions,
                'aspectRatio' => $this->aspectRatio,
            ])->reject(fn ($item) => is_null($item))
                ->toArray(),
        ];
    }
}
