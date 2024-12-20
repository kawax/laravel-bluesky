<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CID;
use Revolution\Bluesky\Core\Varint;
use Tests\TestCase;
use TypeError;

class CoreTest extends TestCase
{
    public function test_cbor_encode(): void
    {
        $json = File::json(__DIR__.'/fixture/3juf3jiip3l2x.json');
        $expect_cid = $json['cid'];

        $cbor = CBOR::encode($json['value']);

        $decode = CBOR::decode($cbor);

        $cbor = CBOR::encode($decode);

        $cid = CID::encode($cbor, codec: CID::DAG_CBOR);

        $this->assertSame($json['value'], $decode);
        $this->assertSame($expect_cid, $cid);

        $str = CBOR::encode('a');
        $str = CBOR::decode($str);
        $this->assertSame('a', $str);

        $int = CBOR::encode(1);
        $int = CBOR::decode($int);
        $this->assertSame(1, $int);
    }

    public function test_cbor_from_array_2(): void
    {
        $json = File::json(__DIR__.'/fixture/3jt6walwmos2y.json');
        $expect_cid = $json['cid'];

        $cbor = CBOR::encode($json['value']);

        [$decode] = CBOR::decodeFirst($cbor);

        $cbor_encoded = CBOR::encode($decode);
        $cid = CID::encode($cbor_encoded, codec: CID::DAG_CBOR);

        $this->assertSame($json['value'], $decode);
        $this->assertSame($cbor, $cbor_encoded);
        $this->assertSame(225, strlen($cbor));
        $this->assertSame(225, strlen($cbor_encoded));

        $this->assertSame($expect_cid, $cid);

        $this->assertTrue(CID::verify($cbor_encoded, $cid, codec: CID::DAG_CBOR));
    }

    public function test_cbor_encode_all(): void
    {
        $json = File::json(__DIR__.'/fixture/3juf3jiip3l2x.json');
        $expect_cid = $json['cid'];

        $cbor = CBOR::encode($json['value']);

        $str = CBOR::encode('a');
        $int = CBOR::encode(1);
        $decode = CBOR::decodeAll($str.$cbor.$int);

        $cbor = CBOR::encode($decode[1]);

        $cid = CID::encode($cbor, codec: CID::DAG_CBOR);

        $this->assertSame($json['value'], $decode[1]);
        $this->assertSame($expect_cid, $cid);
        $this->assertSame('a', $decode[0]);
        $this->assertSame(1, $decode[2]);
    }

    public function test_varint(): void
    {
        $this->assertSame("\x80\x01", Varint::encode(0x80));
        $this->assertSame("\xFF\x01", Varint::encode(0xFF));
        $this->assertSame("\xAC\x02", Varint::encode(0x012C));
        $this->assertSame("\x80\x80\x01", Varint::encode(0x4000));
        $this->assertSame("\x83\x01", Varint::encode(131));
        $this->assertSame("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\x7F", Varint::encode(PHP_INT_MAX));

        $this->assertSame(0x80, Varint::decode("\x80\x01"));
        $this->assertSame(0xFF, Varint::decode("\xFF\x01"));
        $this->assertSame(0x012C, Varint::decode("\xAC\x02"));
        $this->assertSame(0x4000, Varint::decode("\x80\x80\x01"));
        $this->assertSame(131, Varint::decode("\x83\x01"));
        $this->assertSame(PHP_INT_MAX, Varint::decode("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\x7F"));

        $this->assertSame(0x4000, Varint::decodeStream(Utils::streamFor("\x80\x80\x01")));
        $this->assertSame(PHP_INT_MAX, Varint::decodeStream(Utils::streamFor("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\x7F\xFF\xFF")));
    }

    public function test_varint_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->assertSame(0, Varint::decode("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\x80"));
    }

    public function test_varint_invalid_length(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->assertSame(0, Varint::decode("\x80\x80\x80\x80\x80\x80\x80\x71\x80\x80"));
    }

    public function test_varint_invalid_minimally(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->assertSame(0, Varint::decode("\x80\x00"));
    }

    public function test_varint_type_error(): void
    {
        $this->expectException(TypeError::class);

        $this->assertSame('', Varint::encode(PHP_INT_MAX + 1));
    }
}
