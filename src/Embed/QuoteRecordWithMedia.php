<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Embed;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Union\App\Bsky\Embed\AbstractRecordWithMedia;
use Revolution\Bluesky\Types\StrongRef;

final class QuoteRecordWithMedia extends AbstractRecordWithMedia implements Arrayable
{
    public function __construct(
        StrongRef $record,
        Images|Video|External $media,
    ) {
        $this->record = $record->toArray();
        $this->media = $media->toArray();
    }

    public static function create(StrongRef $record, Images|Video|External $media): self
    {
        return new self($record, $media);
    }

    public function toArray(): array
    {
        return [
            '$type' => self::NSID,
            'record' => $this->record,
            'media' => $this->media,
        ];
    }
}
