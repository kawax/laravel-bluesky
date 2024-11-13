<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Embed;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Enum\Embed;
use Revolution\AtProto\Lexicon\Types\AbstractUnion;
use Revolution\Bluesky\Types\BlobRef;

final class External extends AbstractUnion implements Arrayable
{
    public function __construct(
        private readonly string $title,
        private readonly string $description,
        private readonly string $uri,
        private readonly null|array|BlobRef|Closure $thumb = null,
    ) {
        $this->type = Embed::External->value;
    }

    public static function create(string $title, string $description, string $uri, null|array|BlobRef|Closure $thumb = null): self
    {
        return new self(...func_get_args());
    }

    public function toArray(): array
    {
        $thumb = $this->thumb;

        if (is_callable($thumb)) {
            $thumb = call_user_func($this->thumb);
        }

        if ($thumb instanceof BlobRef) {
            $thumb = $thumb->toArray();
        }

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
