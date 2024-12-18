<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use GuzzleHttp\Psr7\Utils;
use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CID;
use Revolution\Bluesky\Core\Varint;
use Tests\TestCase;

class CoreTest extends TestCase
{
    public function test_cbor_encode()
    {
        $json = json_decode(file_get_contents(__DIR__.'/fixture/3juf3jiip3l2x.json'), true);
        $expect_cid = $json['cid'];

        $cbor = CBOR::encode($json['value']);

        $decode = CBOR::decode($cbor);

        $cbor = CBOR::encode($decode);

        $cid = CID::encode($cbor, codec: CID::DAG_CBOR);

        $this->assertSame($json['value'], $decode);
        $this->assertSame($expect_cid, $cid);
    }

    public function test_cbor_from_array_2()
    {
        $json = json_decode(file_get_contents(__DIR__.'/fixture/3jt6walwmos2y.json'), true);
        $expect_cid = $json['cid'];

        $cbor = CBOR::encode($json['value']);

        [$decode] = CBOR::decodeFirst($cbor);

        $cbor_encoded = CBOR::encode($decode);
        $cid = CID::encode($cbor_encoded, codec: CID::DAG_CBOR);

        $this->assertEquals($json['value'], $decode);
        $this->assertSame($json['value']['text'], $decode['text']);
        $this->assertSame($cbor, $cbor_encoded);
        $this->assertSame(225, strlen($cbor));
        $this->assertSame(225, strlen($cbor_encoded));

        $this->assertSame($expect_cid, $cid);

        $this->assertTrue(CID::verify($cbor_encoded, $cid, codec: CID::DAG_CBOR));
    }

    public function test_varint()
    {
        $this->assertSame("\x80\x01", Varint::encode(0x80));
        $this->assertSame("\xFF\x01", Varint::encode(0xFF));
        $this->assertSame("\xAC\x02", Varint::encode(0x012C));
        $this->assertSame("\x80\x80\x01", Varint::encode(0x4000));
        $this->assertSame("\x83\x01", Varint::encode(131));

        $this->assertSame(0x80, Varint::decode("\x80\x01"));
        $this->assertSame(0xFF, Varint::decode("\xFF\x01"));
        $this->assertSame(0x012C, Varint::decode("\xAC\x02"));
        $this->assertSame(0x4000, Varint::decode("\x80\x80\x01"));
        $this->assertSame(131, Varint::decode("\x83\x01"));

        $this->assertSame(0x4000, Varint::decodeStream(Utils::streamFor("\x80\x80\x01")));
    }
}
