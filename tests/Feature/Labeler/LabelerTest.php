<?php

declare(strict_types=1);

namespace Tests\Feature\Labeler;

use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CBOR\AtBytes;
use Revolution\Bluesky\Core\CBOR\MapKeySort;
use Revolution\Bluesky\Labeler\Labeler;
use Revolution\Bluesky\Labeler\SavedLabel;
use Revolution\Bluesky\Labeler\UnsignedLabel;
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

    public function test_labeler_saved(): void
    {
        $unsigned = new UnsignedLabel(
            uri: 'uri',
            cid: null,
            val: 'val',
            src: 'src',
            cts: now()->milli(0)->toISOString(),
            exp: null,
            neg: false,
        );

        [$signed, $sign] = Labeler::signLabel($unsigned);

        $saved = new SavedLabel(id: 1, signed: $signed);

        $this->assertSame($unsigned->uri, $saved->uri);
        $this->assertSame('cts', collect($saved->toEmit())->keys()[0]);
    }
}
