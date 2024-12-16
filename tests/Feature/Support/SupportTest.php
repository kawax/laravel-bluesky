<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use CBOR\MapObject;
use CBOR\TextStringObject;
use Firebase\JWT\JWT;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\AtUri;
use Revolution\Bluesky\Support\CAR;
use Revolution\Bluesky\Support\CBOR;
use Revolution\Bluesky\Support\CID;
use Revolution\Bluesky\Support\DID;
use Revolution\Bluesky\Support\DidDocument;
use Revolution\Bluesky\Support\TID;
use Revolution\Bluesky\Support\Varint;
use RuntimeException;
use Tests\TestCase;
use YOCLIB\Multiformats\Multibase\Multibase;

class SupportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_did_document()
    {
        $didDoc = DidDocument::make([
            '@context' => [],
            'id' => 'did:plc:test',
            'alsoKnownAs' => [
                'at://alice.test',
                'at://alice2.test',
            ],
            'verificationMethod' => [
                [
                    'id' => '',
                    'type' => 'Multikey',
                    'controller' => '',
                    'publicKeyMultibase' => 'ztest',
                ],
            ],
            'service' => [
                [
                    'id' => '#test',
                    'type' => 'AtprotoPersonalDataServer',
                    'serviceEndpoint' => 'https://test',
                ],
                [
                    'id' => '#atproto_pds',
                    'type' => 'AtprotoPersonalDataServer',
                    'serviceEndpoint' => 'https://pds',
                ],
            ],
        ]);

        $this->assertSame('did:plc:test', $didDoc->toArray()['id']);
        $this->assertSame('did:plc:test', $didDoc->id());
        $this->assertSame('alice.test', $didDoc->handle());
        $this->assertSame('https://pds', $didDoc->pdsUrl());
        $this->assertSame('ztest', $didDoc->publicKey());
    }

    public function test_did_document_make()
    {
        Http::fakeSequence()
            ->push([
                '@context' => [],
                'id' => 'did:plc:test',
                'alsoKnownAs' => [
                    'at://alice.test',
                    'at://alice2.test',
                ],
                'verificationMethod' => [],
                'service' => [
                    [
                        'id' => '#test',
                        'type' => 'AtprotoPersonalDataServer',
                        'serviceEndpoint' => 'https://test',
                    ],
                    [
                        'id' => '#atproto_pds',
                        'type' => 'AtprotoPersonalDataServer',
                        'serviceEndpoint' => 'https://pds',
                    ],
                ],
            ]);

        $didDoc = DidDocument::make(Bluesky::identity()->resolveDID('did:plc:test')->json());

        $this->assertSame('did:plc:test', $didDoc->id());
        $this->assertSame('alice.test', $didDoc->handle());
        $this->assertSame('https://pds', $didDoc->pdsUrl());
        $this->assertSame([], $didDoc->get('verificationMethod'));
    }

    public function test_at_uri()
    {
        $at = AtUri::parse('at://did:plc:test/app.bsky.feed.post/abcde');

        $this->assertSame('did:plc:test', $at->repo());
        $this->assertSame('app.bsky.feed.post', $at->collection());
        $this->assertSame('abcde', $at->rkey());
    }

    public function test_at_uri_invalid()
    {
        $this->expectException(RuntimeException::class);

        $at = AtUri::parse('http://did:plc:test/app.bsky.feed.post/abcde');
    }

    public function test_at_uri_to_string()
    {
        $at = AtUri::parse('at://did:plc:test/app.bsky.feed.post/abcde?test=a#hash');

        $this->assertSame('at://did:plc:test/app.bsky.feed.post/abcde?test=a#hash', (string) $at);
        $this->assertSame('at://did:plc:test/app.bsky.feed.post/abcde?test=a#hash', $at->__toString());
    }

    public function test_at_uri_make()
    {
        $at = AtUri::make(repo: 'did:plc:test', collection: 'app.bsky.feed.post', rkey: 'abcde');
        $at2 = AtUri::make(repo: 'did:plc:test', collection: 'app.bsky.feed.post');
        $at3 = AtUri::make(repo: 'did:plc:test');

        $this->assertSame('at://did:plc:test/app.bsky.feed.post/abcde', (string) $at);
        $this->assertSame('at://did:plc:test/app.bsky.feed.post', $at2->__toString());
        $this->assertSame('at://did:plc:test', $at3->__toString());
    }

    public function test_did_web()
    {
        $web = DID::web();
        $example = DID::web('https://example.com/test');

        $this->assertSame('did:web:localhost', $web);
        $this->assertSame('did:web:example.com', $example);
    }

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

    public function test_cid_encode()
    {
        $cid = CID::encode('test');

        $this->assertSame('bafkreie7q3iidccmpvszul7kudcvvuavuo7u6gzlbobczuk5nqk3b4akba', $cid);
    }

    public function test_cid_encode_cbor()
    {
        $cid = CID::encode(CBOR::fromArray(['test' => 'test']), codec: CID::DAG_CBOR);

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

        $this->assertSame(CID::CID_V1, $cid['version']);
        $this->assertSame($hash, $cid['hash']);
    }

    public function test_cid_decode_cbor()
    {
        $cid = CID::decode('bafyreib3h3z3a5jwjcthjojoqjpzrlzly53ycpctnmfsijdk3qb5m3qcdq');

        $this->assertSame(CID::CID_V1, $cid['version']);
        $this->assertSame(CID::DAG_CBOR, $cid['codec']);
    }

    public function test_cid_decode_v0()
    {
        $decode = CID::decodeV0('zQmNX6Tffavsya4xgBi2VJQnSuqy9GsxongxZZ9uZBqp16d');

        $this->assertSame(CID::CID_V0, $decode['version']);
        $this->assertSame(CID::DAG_PB, $decode['codec']);
        $this->assertSame(CID::SHA2_256, $decode['hash_algo']);
        $this->assertSame('02acecc5de2438ea4126a3010ecb1f8a599c8eff22fff1a1dcffe999b27fd3de', $decode['hash']);
    }

    public function test_cid_encode_dag_cbor()
    {
        $cbor = TextStringObject::create('test');
        $cid = CID::encode((string) $cbor, codec: CID::DAG_CBOR);

        $this->assertSame('bafyreidp4mma64aasbuxfbnmdyhi3raaewjxhv53styldknqq3t3uiw4hu', $cid);

        $this->assertTrue(CID::verify((string) $cbor, $cid, codec: CID::DAG_CBOR));

        $decode = CID::decode($cid);

        $this->assertSame(CID::DAG_CBOR, $decode['codec']);
    }

    public function test_cid_cbor()
    {
        $cbor = MapObject::create()
            ->add(
                TextStringObject::create('text'),
                TextStringObject::create(".... And we're back!\n\nOur database upgrade is now complete. Thanks for your patience!"),
            )
            ->add(
                TextStringObject::create('$type'),
                TextStringObject::create('app.bsky.feed.post'),
            )
            ->add(
                TextStringObject::create('createdAt'),
                TextStringObject::create('2023-04-27T21:52:19.545Z'),
            );

        $cid = CID::encode((string) $cbor, codec: CID::DAG_CBOR);

        $this->assertSame('bafyreicpyp7tho7non7ozonty5mqjay6g4l76ntfm7qhvj5p344syd3toy', $cid);

        $this->assertTrue(CID::verify((string) $cbor, $cid, codec: CID::DAG_CBOR));

        $decode = CID::decode($cid);

        $this->assertSame(CID::DAG_CBOR, $decode['codec']);
    }

    public function test_cbor_from_array()
    {
        $json = json_decode(file_get_contents(__DIR__.'/fixture/3juf3jiip3l2x.json'), true);
        $expect_cid = $json['cid'];

        $cbor = CBOR::fromArray($json['value']);

        /** @var MapObject $decode */
        $decode = CBOR::decode($cbor);
        //dump($decode->normalize());

        $cid = CID::encode($cbor, codec: CID::DAG_CBOR);

        $this->assertSame($json['value'], $decode->normalize());
        $this->assertSame($expect_cid, $cid);
    }

    public function test_cbor_from_array_2()
    {
        $json = json_decode(file_get_contents(__DIR__.'/fixture/3jt6walwmos2y.json'), true);
        $expect_cid = $json['cid'];

        $cbor = CBOR::fromArray($json['value']);

        $decode = CBOR::decode($cbor)->normalize();
        $decode = CBOR::normalize($decode);

        $cbor_encoded = CBOR::fromArray($decode);
        $cid = CID::encode($cbor_encoded, codec: CID::DAG_CBOR);

        $this->assertEquals($json['value'], $decode);
        $this->assertSame($json['value']['text'], $decode['text']);

        // TODO: still not working
        $this->assertNotSame($expect_cid, $cid);
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
        [$roots, $blocks] = CAR::decode(file_get_contents(__DIR__.'/fixture/carv1-basic.car'));

        //dump($blocks);
        $this->assertCount(2, $roots);
        $this->assertCount(8, $blocks);
        $this->assertArrayHasKey('bafyreihyrpefhacm6kkp4ql6j6udakdit7g3dmkzfriqfykhjw6cad5lrm', $blocks);
        $this->assertArrayHasKey('QmNX6Tffavsya4xgBi2VJQnSuqy9GsxongxZZ9uZBqp16d', $blocks);
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
        $this->assertSame(CID::CID_V1, $ver1);
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
        $data = Utils::streamFor(utils::tryFopen(__DIR__.'/fixture/bsky-app.car', 'rb'));

        $roots = CAR::decodeRoots($data);
        $this->assertCount(1, $roots);

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
        $header = CBOR::decode($data->read($header_len))->normalize();
        //dump($header);

        $data->seek(1 + $header_len);

        $data_len = Varint::decode($data->read(8));
        $pos = strlen(Varint::encode($data_len));
        $data->seek(1 + $header_len + $pos);
        $this->assertSame(209, $data_len);
        $this->assertSame(2, $pos);

        $ver1 = Varint::decode($data->read(1));
        $this->assertSame(CID::CID_V1, $ver1);

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
        $this->assertIsArray(CBOR::decode($block)->normalize());

        $current = $data->tell();

        //2
        $data_len = Varint::decode($data->read(8));
        $pos = strlen(Varint::encode($data_len));
        $data->seek($current + $pos);
        $this->assertSame(1733, $data_len);
        $this->assertSame(2, $pos);

        $ver1 = Varint::decode($data->read(1));
        $this->assertSame(CID::CID_V1, $ver1);

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
        $this->assertIsArray(CBOR::decode($block)->normalize());

        $current = $data->tell();

        //3
        $data_len = Varint::decode($data->read(8));
        $pos = strlen(Varint::encode($data_len));
        $data->seek($current + $pos);
        $this->assertSame(224, $data_len);
        $this->assertSame(2, $pos);

        $ver1 = Varint::decode($data->read(1));
        $this->assertSame(CID::CID_V1, $ver1);

        $codec = Varint::decode($data->read(1));
        $this->assertSame(CID::DAG_CBOR, $codec);

        $hash_algo = Varint::decode($data->read(1));
        $this->assertSame(CID::SHA2_256, $hash_algo);

        $hash_len = Varint::decode($data->read(1));
        $this->assertSame(32, $hash_len);
    }
}
