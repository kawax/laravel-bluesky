<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CID;
use Tests\TestCase;
use Throwable;

class CidTest extends TestCase
{
    public function test_cid_encode(): void
    {
        $cid = CID::encode('test');

        $this->assertSame('bafkreie7q3iidccmpvszul7kudcvvuavuo7u6gzlbobczuk5nqk3b4akba', $cid);
    }

    public function test_cid_encode_cbor(): void
    {
        $record = [
            'text' => 'Hello, Bluesky!',
            '$type' => 'app.bsky.feed.post',
            'createdAt' => '2025-01-01T00:00:00.000Z',
        ];

        $cid = CID::encode(CBOR::encode($record), codec: CID::DAG_CBOR);

        $this->assertSame('bafyreih5y47li4zuvvzevmq4xl7woqxchfc2pnfclv3kfz3zefb2qd3bzm', $cid);
    }

    public function test_cid_verify(): void
    {
        $this->assertTrue(CID::verify('test', 'bafkreie7q3iidccmpvszul7kudcvvuavuo7u6gzlbobczuk5nqk3b4akba'));
        $this->assertFalse(CID::verify('test2', 'bafkreie7q3iidccmpvszul7kudcvvuavuo7u6gzlbobczuk5nqk3b4akba', codec: null));
    }

    public function test_cid_decode(): void
    {
        $cid = CID::decode('bafkreie7q3iidccmpvszul7kudcvvuavuo7u6gzlbobczuk5nqk3b4akba');

        $hash = hash('sha256', 'test');

        $this->assertSame(CID::V1, $cid['version']);
        $this->assertSame($hash, $cid['hash']);
    }

    public function test_cid_decode_cbor(): void
    {
        $cid = CID::decode('bafyreib3h3z3a5jwjcthjojoqjpzrlzly53ycpctnmfsijdk3qb5m3qcdq');

        $this->assertSame(CID::V1, $cid['version']);
        $this->assertSame(CID::DAG_CBOR, $cid['codec']);
    }

    public function test_cid_decode_v0(): void
    {
        // If v0 is specified, "zQm" is also allowed.
        $decode = CID::decodeV0('zQmNX6Tffavsya4xgBi2VJQnSuqy9GsxongxZZ9uZBqp16d');
        // When automatic detection is used, "zQm" is not allowed.
        $decode = CID::decode('QmNX6Tffavsya4xgBi2VJQnSuqy9GsxongxZZ9uZBqp16d');

        $this->assertSame(CID::V0, $decode['version']);
        $this->assertSame(CID::DAG_PB, $decode['codec']);
        $this->assertSame(CID::SHA2_256, $decode['hash_algo']);
        $this->assertSame('02acecc5de2438ea4126a3010ecb1f8a599c8eff22fff1a1dcffe999b27fd3de', $decode['hash']);
    }

    public function test_cid_detect(): void
    {
        $this->assertSame(CID::V0, CID::version('QmNX6Tffavsya4xgBi2VJQnSuqy9GsxongxZZ9uZBqp16d'));
        $this->assertSame(CID::V1, CID::version('bafyreib3h3z3a5jwjcthjojoqjpzrlzly53ycpctnmfsijdk3qb5m3qcdq'));
    }

    public function test_cid_detect_throw(): void
    {
        $this->expectException(Throwable::class);

        $this->assertSame(CID::V0, CID::version('zQmNX6Tffavsya4xgBi2VJQnSuqy9GsxongxZZ9uZBqp16d'));
    }

    public function test_cid_encode_dag_cbor(): void
    {
        $cbor = CBOR::encode('test');
        $cid = CID::encode($cbor, codec: CID::DAG_CBOR);

        $this->assertSame('bafyreidp4mma64aasbuxfbnmdyhi3raaewjxhv53styldknqq3t3uiw4hu', $cid);

        $this->assertTrue(CID::verify((string) $cbor, $cid, codec: CID::DAG_CBOR));

        $decode = CID::decode($cid);

        $this->assertSame(CID::DAG_CBOR, $decode['codec']);
    }
}
