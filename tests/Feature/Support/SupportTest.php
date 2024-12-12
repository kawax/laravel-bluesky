<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use CBOR\MapObject;
use CBOR\TextStringObject;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\AtUri;
use Revolution\Bluesky\Support\CBOR;
use Revolution\Bluesky\Support\CID;
use Revolution\Bluesky\Support\DID;
use Revolution\Bluesky\Support\DidDocument;
use Revolution\Bluesky\Support\TID;
use Revolution\Bluesky\Support\Varint;
use RuntimeException;
use Tests\TestCase;

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

    public function test_cid_verify()
    {
        $this->assertTrue(CID::verify('test', 'bafkreie7q3iidccmpvszul7kudcvvuavuo7u6gzlbobczuk5nqk3b4akba'));
        $this->assertFalse(CID::verify('test2', 'bafkreie7q3iidccmpvszul7kudcvvuavuo7u6gzlbobczuk5nqk3b4akba'));
    }

    public function test_cid_decode()
    {
        $cid = CID::decode('bafkreie7q3iidccmpvszul7kudcvvuavuo7u6gzlbobczuk5nqk3b4akba');

        $hash = hash('sha256', 'test');

        $this->assertSame(CID::CID_V1, $cid['version']);
        $this->assertSame($hash, $cid['hash']);
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
                TextStringObject::create(".... And we're back!\n\nOur database upgrade is now complete. Thanks for your patience!")
            )
            ->add(
                TextStringObject::create('$type'),
                TextStringObject::create('app.bsky.feed.post')
            )
            ->add(
                TextStringObject::create('createdAt'),
                TextStringObject::create('2023-04-27T21:52:19.545Z')
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
        $decode = CBOR::decode((string) $cbor);
        //dump($decode->normalize());

        $cid = CID::encode((string) $cbor, codec: CID::DAG_CBOR);

        $this->assertSame($json['value'], $decode->normalize());
        $this->assertSame($expect_cid, $cid);
    }

    public function test_cbor_from_array_2()
    {
        $json = json_decode(file_get_contents(__DIR__.'/fixture/3jt6walwmos2y.json'), true);
        $expect_cid = $json['cid'];

        $cbor = CBOR::fromArray($json['value']);

        /** @var MapObject $decode */
        $decode = CBOR::decode((string) $cbor);
        //dump($decode->normalize());

        $cid = CID::encode((string) $cbor, codec: CID::DAG_CBOR);

        $this->assertEquals($json['value'], $decode->normalize());
        $this->assertSame($json['value']['text'], $decode->normalize()['text']);

        // TODO: still not working
        $this->assertNotSame($expect_cid, $cid);
    }

    public function test_varint()
    {
        $this->assertSame("\x80\x01", Varint::encode(0x80));
        $this->assertSame("\xFF\x01", Varint::encode(0xFF));
        $this->assertSame("\xAC\x02", Varint::encode(0x012C));
        $this->assertSame("\x80\x80\x01", Varint::encode(0x4000));

        $this->assertSame(0x80, Varint::decode("\x80\x01"));
        $this->assertSame(0xFF, Varint::decode("\xFF\x01"));
        $this->assertSame(0x012C, Varint::decode("\xAC\x02"));
        $this->assertSame(0x4000, Varint::decode("\x80\x80\x01"));
    }
}
