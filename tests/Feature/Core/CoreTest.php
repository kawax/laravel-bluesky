<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Firebase\JWT\JWT;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Revolution\Bluesky\Core\CAR;
use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CID;
use Revolution\Bluesky\Core\Protobuf;
use Revolution\Bluesky\Core\TID;
use Revolution\Bluesky\Core\Varint;
use Tests\TestCase;
use Throwable;
use YOCLIB\Multiformats\Multibase\Multibase;

class CoreTest extends TestCase
{
    public function test_tid_encode()
    {
        $time = now()->getPreciseTimestamp();
        $encode = TID::s32encode($time);
        $decode = TID::s32decode($encode);

        $this->assertSame((int) $time, $decode);
    }

    public function test_tid_next()
    {
        $this->freezeTime(function () {
            $tid = TID::next();
            $tid2 = TID::next();

            $this->assertMatchesRegularExpression(TID::FORMAT, $tid->toString());
            $this->assertTrue($tid->olderThen($tid2));
            $this->assertTrue($tid2->newerThen($tid));
        });
    }

    public function test_tid_next_str()
    {
        $tid = TID::nextStr();

        $this->assertSame(13, strlen($tid));
        $this->assertMatchesRegularExpression(TID::FORMAT, $tid);
    }

    public function test_tid_from_str()
    {
        $tid_str = TID::nextStr();
        $tid = TID::fromStr($tid_str);

        $this->assertMatchesRegularExpression(TID::FORMAT, $tid->toString());
        $this->assertSame($tid_str, $tid->toString());
    }

    public function test_tid_from_time()
    {
        $time = now()->getPreciseTimestamp();
        $clockId = 31;
        $tid = TID::fromTime($time, $clockId);

        $this->assertSame((int) $time, $tid->timestamp());
        $this->assertSame($clockId, $tid->clockId());
    }

    public function test_tid_equals()
    {
        $tid = TID::next();
        $tid2 = clone $tid;

        $this->assertTrue($tid->equals($tid2));
    }

    public function test_tid_date()
    {
        $time = now();
        $tid = TID::fromTime($time->getPreciseTimestamp(), 1);

        $decode = TID::fromStr((string) $tid)->toDate();

        $this->assertTrue($time->eq($decode));
    }

    public function test_tid_prev()
    {
        $this->travel(1)->hour();
        $prev = TID::next();

        $this->travelBack();
        $tid = TID::next($prev);

        $this->assertTrue($tid->newerThen($prev));
        $this->assertSame($tid->timestamp(), $prev->timestamp() + 1);
    }

    public function test_tid_prev_str()
    {
        $this->freezeTime(function () {
            $prev = TID::nextStr();
            $tid = TID::nextStr($prev);

            $this->assertTrue($tid > $prev);
        });
    }

    public function test_tid_invalid_len()
    {
        $this->expectException(InvalidArgumentException::class);

        $tid = TID::fromStr('invalid');
    }

    public function test_tid_invalid_match()
    {
        $this->expectException(InvalidArgumentException::class);

        $tid = TID::fromStr('0000000000000');
    }

    public function test_tid_is()
    {
        $this->assertTrue(TID::is('3jzfcijpj2z2a'));
        $this->assertTrue(TID::is('7777777777777'));
        $this->assertTrue(TID::is('3zzzzzzzzzzzz'));

        $this->assertFalse(TID::is('3jzfcijpj2z21'));
        $this->assertFalse(TID::is('0000000000000'));
        $this->assertFalse(TID::is('3jzfcijpj2z2aa'));
        $this->assertFalse(TID::is('3jzfcijpj2z2'));
        $this->assertFalse(TID::is('3jzf-cij-pj2z-2a'));
        $this->assertFalse(TID::is('zzzzzzzzzzzzz'));
        $this->assertFalse(TID::is('kjzfcijpj2z2a'));
    }

    public function test_tid_parse()
    {
        $tid = TID::fromStr('3jt6walwmos2y');

        $this->assertSame(1681321002683032, $tid->timestamp());
        $this->assertSame(30, $tid->clockId());
        $this->assertSame('3jt6walwmos2y', TID::fromTime($tid->timestamp(), $tid->clockId())->toString());
    }

    public function test_cid_encode()
    {
        $cid = CID::encode('test');

        $this->assertSame('bafkreie7q3iidccmpvszul7kudcvvuavuo7u6gzlbobczuk5nqk3b4akba', $cid);
    }

    public function test_cid_encode_cbor()
    {
        $cid = CID::encode(CBOR::encode(['test' => 'test']), codec: CID::DAG_CBOR);

        $this->assertSame('bafyreib3h3z3a5jwjcthjojoqjpzrlzly53ycpctnmfsijdk3qb5m3qcdq', $cid);
    }

    public function test_cid_verify()
    {
        $this->assertTrue(CID::verify('test', 'bafkreie7q3iidccmpvszul7kudcvvuavuo7u6gzlbobczuk5nqk3b4akba'));
        $this->assertFalse(CID::verify('test2', 'bafkreie7q3iidccmpvszul7kudcvvuavuo7u6gzlbobczuk5nqk3b4akba', codec: null));
    }

    public function test_cid_decode()
    {
        $cid = CID::decode('bafkreie7q3iidccmpvszul7kudcvvuavuo7u6gzlbobczuk5nqk3b4akba');

        $hash = hash('sha256', 'test');

        $this->assertSame(CID::V1, $cid['version']);
        $this->assertSame($hash, $cid['hash']);
    }

    public function test_cid_decode_cbor()
    {
        $cid = CID::decode('bafyreib3h3z3a5jwjcthjojoqjpzrlzly53ycpctnmfsijdk3qb5m3qcdq');

        $this->assertSame(CID::V1, $cid['version']);
        $this->assertSame(CID::DAG_CBOR, $cid['codec']);
    }

    public function test_cid_decode_v0()
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

    public function test_cid_detect()
    {
        $this->assertSame(CID::V0, CID::detect('QmNX6Tffavsya4xgBi2VJQnSuqy9GsxongxZZ9uZBqp16d'));
        $this->assertSame(CID::V1, CID::detect('bafyreib3h3z3a5jwjcthjojoqjpzrlzly53ycpctnmfsijdk3qb5m3qcdq'));
    }

    public function test_cid_detect_throw()
    {
        $this->expectException(Throwable::class);

        $this->assertSame(CID::V0, CID::detect('zQmNX6Tffavsya4xgBi2VJQnSuqy9GsxongxZZ9uZBqp16d'));
    }

    public function test_cid_encode_dag_cbor()
    {
        $cbor = CBOR::encode('test');
        $cid = CID::encode($cbor, codec: CID::DAG_CBOR);

        $this->assertSame('bafyreidp4mma64aasbuxfbnmdyhi3raaewjxhv53styldknqq3t3uiw4hu', $cid);

        $this->assertTrue(CID::verify((string) $cbor, $cid, codec: CID::DAG_CBOR));

        $decode = CID::decode($cid);

        $this->assertSame(CID::DAG_CBOR, $decode['codec']);
    }

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

    public function test_car_basic()
    {
        $data = file_get_contents(__DIR__.'/fixture/carv1-basic.car');
        $roots = iterator_to_array(CAR::decodeRoots($data));
        $blocks = iterator_to_array(CAR::blockIterator($data));

        //dump($blocks);
        $this->assertCount(2, $roots);
        $this->assertCount(8, $blocks);
        $this->assertArrayHasKey('bafyreihyrpefhacm6kkp4ql6j6udakdit7g3dmkzfriqfykhjw6cad5lrm', $blocks);
        $this->assertArrayHasKey('QmNX6Tffavsya4xgBi2VJQnSuqy9GsxongxZZ9uZBqp16d', $blocks);
    }

    public function test_car_basic_map()
    {
        $data = Utils::streamFor(Utils::tryFopen(__DIR__.'/fixture/carv1-basic.car', 'rb'));

        $blocks = iterator_to_array(CAR::blockMap($data));

        $this->assertEmpty($blocks);
    }

    public function test_car_basic_protobuf()
    {
        $data = Utils::streamFor(Utils::tryFopen(__DIR__.'/fixture/carv1-basic.car', 'rb'));
        $blocks = iterator_to_array(CAR::blockIterator($data));

        $expect_cid = 'QmNX6Tffavsya4xgBi2VJQnSuqy9GsxongxZZ9uZBqp16d';
        $block = $blocks[$expect_cid];

        $encode = Protobuf::encode($block);
        $cid = CID::encodeV0($encode);

        $decode = Protobuf::decode($encode);

        $this->assertSame($expect_cid, $cid);
        $this->assertSame($block, $decode);
    }

    public function test_car_basic_stream()
    {
        $data = Utils::streamFor(Utils::tryFopen(__DIR__.'/fixture/carv1-basic.car', 'rb'));

        $data->seek(100);
        $block_len = Varint::decode($data->read(8));
        $pos = strlen(Varint::encode($block_len));
        $data->seek(100 + $pos);
        $this->assertSame(91, $block_len);
        $ver1 = Varint::decode($data->read(1));
        $ver0 = $data->read(2);
        $this->assertSame(CID::V1, $ver1);
        $this->assertNotSame("\x12\x20", $ver0);

        $data->seek(192);
        $block_len = Varint::decode($data->read(8));
        $pos = strlen(Varint::encode($block_len));
        $data->seek(192 + $pos);
        $this->assertSame(131, $block_len);
        $ver0 = $data->read(2);
        $this->assertSame("\x12\x20", $ver0);

        $data->seek(100);
        $block = $data->read(92);
        $this->assertSame(91, Varint::decode(substr($block, 0, 1)));

        $data->seek(325);
        $this->assertSame(40, Varint::decode($data->read(1)));

        $data->seek(362);
        $this->assertSame('Y2NjYw', JWT::urlsafeB64Encode($data->read(4)));
    }

    public function test_car_download_repo()
    {
        $data = file_get_contents(__DIR__.'/fixture/bsky-app.car');

        $roots = CAR::decodeRoots($data);
        $this->assertCount(1, $roots);

        $blocks = iterator_to_array(CAR::blockIterator($data));
        $this->assertArrayHasKey($roots[0], $blocks);
    }

    public function test_car_download_repo_stream()
    {
        $data = Utils::streamFor(Utils::tryFopen(__DIR__.'/fixture/bsky-app.car', 'rb'));

        [$roots, $blocks] = CAR::decode($data);
        $this->assertCount(1, $roots);
        $this->assertCount(744, $blocks);
    }

    public function test_car_block_iterator()
    {
        $data = Utils::streamFor(Utils::tryFopen(__DIR__.'/fixture/bsky-app.car', 'rb'));

        foreach (CAR::blockIterator($data) as $cid => $block) {
            //dump($cid, $block);
            $this->assertNotEmpty($cid);
            $this->assertNotEmpty($block);
        }
    }

    public function test_car_download_file_stream()
    {
        $data = Utils::streamFor(Utils::tryFopen(__DIR__.'/fixture/bsky-app.car', 'rb'));

        $header_len = Varint::decode($data->read(1));
        //dump($header_len);
        $header = CBOR::decode($data->read($header_len));
        //dump($header);

        $data->seek(1 + $header_len);

        $data_len = Varint::decode($data->read(8));
        $pos = strlen(Varint::encode($data_len));
        $data->seek(1 + $header_len + $pos);
        $this->assertSame(209, $data_len);
        $this->assertSame(2, $pos);

        $ver1 = Varint::decode($data->read(1));
        $this->assertSame(CID::V1, $ver1);

        $codec = Varint::decode($data->read(1));
        $this->assertSame(CID::DAG_CBOR, $codec);

        $hash_algo = Varint::decode($data->read(1));
        $this->assertSame(CID::SHA2_256, $hash_algo);

        $hash_len = Varint::decode($data->read(1));
        $this->assertSame(32, $hash_len);

        $data->seek(-4, SEEK_CUR);
        $hash = $data->read(4 + $hash_len);
        $this->assertSame('bafyreig7gxhfmvwk4qzo2aliqytgdiflw6xwi4dkkqwjlgt7zjlvfbptcy', Multibase::encode(Multibase::BASE32, $hash));

        $block_len = $data_len - 4 - $hash_len;

        $block = $data->read($block_len);
        $this->assertIsArray(CBOR::decode($block));

        $current = $data->tell();

        //2
        $data_len = Varint::decode($data->read(8));
        $pos = strlen(Varint::encode($data_len));
        $data->seek($current + $pos);
        $this->assertSame(1733, $data_len);
        $this->assertSame(2, $pos);

        $ver1 = Varint::decode($data->read(1));
        $this->assertSame(CID::V1, $ver1);

        $codec = Varint::decode($data->read(1));
        $this->assertSame(CID::DAG_CBOR, $codec);

        $hash_algo = Varint::decode($data->read(1));
        $this->assertSame(CID::SHA2_256, $hash_algo);

        $hash_len = Varint::decode($data->read(1));
        $this->assertSame(32, $hash_len);

        $data->seek(-4, SEEK_CUR);
        $hash = $data->read(4 + $hash_len);
        $this->assertSame('bafyreiejo5hn2rjcihcfz3txibzg3f7z5cmoxpdmloxamwlrrkgllafqy4', Multibase::encode(Multibase::BASE32, $hash));

        $block_len = $data_len - 4 - $hash_len;

        $block = $data->read($block_len);
        $this->assertIsArray(CBOR::decode($block));

        $current = $data->tell();

        //3
        $data_len = Varint::decode($data->read(8));
        $pos = strlen(Varint::encode($data_len));
        $data->seek($current + $pos);
        $this->assertSame(224, $data_len);
        $this->assertSame(2, $pos);

        $ver1 = Varint::decode($data->read(1));
        $this->assertSame(CID::V1, $ver1);

        $codec = Varint::decode($data->read(1));
        $this->assertSame(CID::DAG_CBOR, $codec);

        $hash_algo = Varint::decode($data->read(1));
        $this->assertSame(CID::SHA2_256, $hash_algo);

        $hash_len = Varint::decode($data->read(1));
        $this->assertSame(32, $hash_len);
    }

    public function test_car_block_map()
    {
        $data = Utils::streamFor(Utils::tryFopen(__DIR__.'/fixture/bsky-app.car', 'rb'));

        $this->assertCount(604, iterator_to_array(CAR::blockMap($data)));
        $this->assertCount(744, iterator_to_array(CAR::blockIterator($data)));

        foreach (CAR::blockMap($data) as $key => $record) {
            //dump($key, $record);
            $this->assertTrue(Str::contains($key, '/'));
            $this->assertTrue(Arr::exists($record, 'uri'));
        }
    }
}
