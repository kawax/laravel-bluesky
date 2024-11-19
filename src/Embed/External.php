<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Embed;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Attributes\Blob;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Enum\Embed;
use Revolution\AtProto\Lexicon\Types\AbstractUnion;
use Revolution\Bluesky\Types\BlobRef;

/**
 * External link / Social card.
 *
 * ```
 * use Revolution\Bluesky\Embed\External;
 * use Revolution\Bluesky\Record\Post;
 *
 * $external = External::create(
 *     title: 'Title',
 *     description: 'Description',
 *     uri: 'https://',
 *     thumb: fn() => Bluesky::uploadBlob(Storage::get('thumb.png'), Storage::mimeType('thumb.png'))->json('blob'),
 * );
 *
 * $post = Post::create('test')->embed($external);
 * ```
 */
final class External extends AbstractUnion implements Arrayable
{
    public function __construct(
        private readonly string $title,
        private readonly string $description,
        #[Format('uri')] private readonly string $uri,
        #[Blob(accept: ['image/*'], maxSize: 1000000)] private readonly null|array|BlobRef|Closure $thumb = null,
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
