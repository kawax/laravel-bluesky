<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Embed;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Enum\Embed;
use Revolution\AtProto\Lexicon\Types\AbstractUnion;
use Revolution\Bluesky\Types\BlobRef;

final class Images extends AbstractUnion implements Arrayable
{
    private array $images = [];

    public function __construct()
    {
        $this->type = Embed::Images->value;
    }

    public static function create(): self
    {
        return new self();
    }

    /**
     * Pass an Array or Blob.
     * ```
     * use Revolution\Bluesky\Types\Blob;
     *
     * $blob = Bluesky::uploadBlob(Storage::get('test.png'), Storage::mimeType('test.png'))->json('blob');
     * $blob = Blob::fromArray($blob);
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
     */
    public function add(string $alt, BlobRef|array|callable $blob): self
    {
        if (is_callable($blob)) {
            $blob = call_user_func($blob);
        }

        if ($blob instanceof BlobRef) {
            $blob = $blob->toArray();
        }

        $this->images[] = [
            'image' => $blob,
            'alt' => $alt,
        ];

        return $this;
    }

    public function toArray(): array
    {
        return [
            '$type' => $this->type,
            'images' => collect($this->images)->take(4)->toArray(),
        ];
    }
}
