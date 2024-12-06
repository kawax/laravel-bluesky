<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Types;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Types\AbstractBlob;

final class BlobRef extends AbstractBlob implements Arrayable
{
    public static function make(#[Format('cid')] string $link, string $mimeType, int $size): self
    {
        $self = new self();

        $self->link = $link;
        $self->mimeType = $mimeType;
        $self->size = $size;

        return $self;
    }

    /**
     * @param  array{"$type": string, ref: array{"$link": string}, mimeType: string, size: int}  $blob
     */
    public static function fromArray(array $blob): self
    {
        $self = new self();

        $self->link = data_get($blob, 'ref.$link');
        $self->mimeType = data_get($blob, 'mimeType');
        $self->size = data_get($blob, 'size');

        return $self;
    }

    /**
     * @return array{"$type": string, ref: array{"$link": string}, mimeType: string, size: int}
     */
    public function toArray(): array
    {
        return [
            '$type' => $this->type,
            'ref' => [
                '$link' => $this->link,
            ],
            'mimeType' => $this->mimeType,
            'size' => $this->size,
        ];
    }
}
