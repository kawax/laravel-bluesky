<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Revolution\Bluesky\Core\CBOR;

final readonly class SubscribeLabelMessage
{
    public function __construct(
        protected int $seq,
        protected array $labels,
    ) {
    }

    public function toBytes(): string
    {
        $header = ['op' => 1, 't' => '#labels'];

        $body = [
            'seq' => $this->seq,
            'labels' => $this->labels,
        ];

        return CBOR::encode($header).CBOR::encode($body);
    }
}
