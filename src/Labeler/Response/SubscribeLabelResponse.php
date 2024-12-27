<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler\Response;

use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Labeler\Labeler;

final readonly class SubscribeLabelResponse
{
    public function __construct(
        protected int $seq,
        protected array $labels,
    ) {
    }

    public function toBytes(): string
    {
        $header = ['op' => 1, 't' => '#labels'];

        $labels = collect($this->labels)
            ->map(fn ($label) => Labeler::formatLabel($label))
            ->toArray();

        $body = [
            'seq' => $this->seq,
            'labels' => $labels,
        ];

        return CBOR::encode($header).CBOR::encode($body);
    }
}
