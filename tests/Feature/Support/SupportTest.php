<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use Illuminate\Support\Facades\Http;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\AtUri;
use Revolution\Bluesky\Support\DID;
use Revolution\Bluesky\Support\DidDocument;
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
        $this->assertSame('at://did:plc:test/app.bsky.feed.post/abcde?test=a#hash', $at->toString());
        $this->assertSame('at://did:plc:test/app.bsky.feed.post/abcde?test=a#hash', $at->__toString());
    }

    public function test_at_uri_make()
    {
        $at = AtUri::make(repo: 'did:plc:test', collection: 'app.bsky.feed.post', rkey: 'abcde');
        $at2 = AtUri::make(repo: 'did:plc:test', collection: 'app.bsky.feed.post');
        $at3 = AtUri::make(repo: 'did:plc:test');

        $this->assertSame('at://did:plc:test/app.bsky.feed.post/abcde', (string) $at);
        $this->assertSame('at://did:plc:test/app.bsky.feed.post', $at2->toString());
        $this->assertSame('at://did:plc:test', $at3->__toString());
    }

    public function test_did_web()
    {
        $web = DID::web();
        $example = DID::web('https://example.com/test');

        $this->assertSame('did:web:localhost', $web);
        $this->assertSame('did:web:example.com', $example);
    }
}
