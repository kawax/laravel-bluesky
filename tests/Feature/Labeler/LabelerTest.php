<?php

declare(strict_types=1);

namespace Tests\Feature\Labeler;

use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CBOR\AtBytes;
use Revolution\Bluesky\Core\CBOR\MapKeySort;
use Tests\TestCase;

class LabelerTest extends TestCase
{
    public function test_labeler_cbor_encode(): void
    {
        $label = [
            'ver' => 1,
            'uri' => '',
            'cid' => null,
            'val' => 'label',
            'src' => 'did',
            'cts' => now()->toISOString(),
            'exp' => null,
            'sig' => new AtBytes('sig'),
        ];

        $label = CBOR::normalize($label);

        uksort($label, new MapKeySort());

        $cbor = CBOR::encode($label);

        $decode = CBOR::decode($cbor);

        $this->assertSame($label, $decode);
    }
}
