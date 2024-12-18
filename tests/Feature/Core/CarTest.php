<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Firebase\JWT\JWT;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use phpseclib3\Crypt\EC;
use Revolution\Bluesky\Core\CAR;
use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CID;
use Revolution\Bluesky\Core\Protobuf;
use Revolution\Bluesky\Core\Varint;
use Revolution\Bluesky\Crypto\DidKey;
use Revolution\Bluesky\Crypto\Signature;
use Revolution\Bluesky\Socialite\Key\OAuthKey;
use Tests\TestCase;
use YOCLIB\Multiformats\Multibase\Multibase;

class CarTest extends TestCase
{
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
        $this->assertSame(242, $data_len);
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
        $this->assertSame('bafyreift3sxgywrkke7bqazypjucn3f3tctvrv3q4i3bzmblq7x6ku3ire', Multibase::encode(Multibase::BASE32, $hash));

        $block_len = $data_len - 4 - $hash_len;

        $block = $data->read($block_len);
        $this->assertIsArray(CBOR::decode($block));

        $current = $data->tell();

        //2
        $data_len = Varint::decode($data->read(8));
        $pos = strlen(Varint::encode($data_len));
        $data->seek($current + $pos);
        $this->assertSame(534, $data_len);
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
        $this->assertSame('bafyreifsbltuvxsxfonlftmif7jhe7j7nfeijd7t5juw4p6shcopyqkmim', Multibase::encode(Multibase::BASE32, $hash));

        $block_len = $data_len - 4 - $hash_len;

        $block = $data->read($block_len);
        $this->assertIsArray(CBOR::decode($block));

        $current = $data->tell();

        //3
        $data_len = Varint::decode($data->read(8));
        $pos = strlen(Varint::encode($data_len));
        $data->seek($current + $pos);
        $this->assertSame(83, $data_len);
        $this->assertSame(1, $pos);

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

    public function test_car_verify_signed_commit()
    {
        $data = Utils::streamFor(Utils::tryFopen(__DIR__.'/fixture/bsky-app.car', 'rb'));

        [$roots, $blocks] = CAR::decode($data);

        $signed_commit = $blocks[$roots[0]];

        $cid = CID::encode(CBOR::encode($signed_commit), codec: CID::DAG_CBOR);
        $this->assertSame($roots[0], $cid);

        $sig = data_get($signed_commit, 'sig.$bytes');
        $sig = base64_decode($sig);
        $sig = Signature::fromCompact($sig);

        $unsigned = Arr::except($signed_commit, 'sig');

        $cbor = CBOR::encode($unsigned);

        $bsky_app = 'zQ3shQo6TF2moaqMTrUZEM1jeuYRQXeHEx4evX9751y2qPqRA';
        $didKey = DidKey::parse($bsky_app);
        $pk = EC::loadPublicKey($didKey['key']);

        $this->assertTrue($pk->verify($cbor, $sig));
    }

    public function test_car_verify_signed()
    {
        $sk = OAuthKey::load();

        $unsigned = [
            'int' => -100,
            'data' => 'test',
            'true' => true,
            'false' => false,
            'float' => 3.14,
            'uint8' => 0x100,
            'uint16' => 0x10000,
            'uint32' => 0x100000000,
            'uint64' => 0x100000001,
        ];

        $unsigned_cbor = CBOR::encode($unsigned);

        $sign = $sk->privateKey()->sign($unsigned_cbor);

        $signed = array_merge($unsigned, ['sig' => ['$bytes' => base64_encode($sign)]]);
        $signed_cbor = CBOR::encode($signed);

        $decode = CBOR::decode($signed_cbor);

        $sig = data_get($decode, 'sig.$bytes');
        $sig = base64_decode($sig);

        $unsigned_decode = Arr::except($decode, 'sig');
        $unsigned_decode_cbor = CBOR::encode($unsigned_decode);

        $pk = $sk->publicKey();

        $this->assertSame($unsigned, $unsigned_decode);
        $this->assertTrue($pk->verify($unsigned_decode_cbor, $sig));
    }
}
