<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Embed;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Union\App\Bsky\Embed\AbstractVideo;
use Revolution\Bluesky\Types\BlobRef;

final class Video extends AbstractVideo implements Arrayable
{
    public function __construct(
        BlobRef|array $video,
        ?string $alt = null,
        ?array $captions = null,
        ?array $aspectRatio = null,
    ) {
        $this->video = $video instanceof BlobRef ? $video->toArray() : $video;
        $this->alt = $alt;
        $this->captions = $captions;
        $this->aspectRatio = $aspectRatio;
    }

    public static function create(BlobRef|array $video, ?string $alt = null, ?array $captions = null, ?array $aspectRatio = null): self
    {
        return new self(...func_get_args());
    }

    public function toArray(): array
    {
        return collect([
            '$type' => self::NSID,
            'video' => $this->video,
            'alt' => $this->alt,
            'captions' => $this->captions,
            'aspectRatio' => $this->aspectRatio,
        ])->reject(fn ($item) => is_null($item))
            ->toArray();
    }
}
