<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Embed;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Union\App\Bsky\Embed\AbstractImages;
use Revolution\Bluesky\Types\BlobRef;

final class Images extends AbstractImages implements Arrayable
{
    public static function create(): self
    {
        return new self();
    }

    /**
     * Pass an Array or BlobRef.
     *
     * ```
     * use Revolution\Bluesky\Types\BlobRef;
     *
     * $blob = Bluesky::uploadBlob(Storage::get('test.png'), Storage::mimeType('test.png'))->json('blob');
     * $blob = BlobRef::fromArray($blob);
     *
     * $images = Images::create()
     *                 ->add(alt: 'ALT TEXT', blob: $blob)
     * ```
     * Passing via closure.
     * ```
     * ->add(alt: 'ALT TEXT', blob: function (): array {
     *     return Bluesky::uploadBlob(Storage::get('test.png'), Storage::mimeType('test.png'))->json('blob');
     * })
     * ```
     *
     * @param  BlobRef|array|callable  $blob
     * @param  ?array{width: int|string, height: int|string}  $aspectRatio
     */
    public function add(string $alt, BlobRef|array|callable $blob, ?array $aspectRatio = null): self
    {
        if (is_callable($blob)) {
            $blob = call_user_func($blob);
        }

        if ($blob instanceof BlobRef) {
            $blob = $blob->toArray();
        }

        $imgData = [
            'image' => $blob,
            'alt' => $alt,
        ];

        if ($aspectRatio) {
            $imgData['aspectRatio'] = $aspectRatio;
        }

        $this->images[] = $imgData;

        return $this;
    }

    public function toArray(): array
    {
        return [
            '$type' => self::NSID,
            'images' => collect($this->images)->take(4)->toArray(),
        ];
    }
}
