<?php

namespace Tests\Feature\Support;

use Illuminate\Support\Facades\Http;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\AtUri;
use Revolution\Bluesky\Support\DidDocument;
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

        $this->assertSame('did:plc:test', $didDoc->toArray()['id']);
        $this->assertSame('did:plc:test', $didDoc->id());
        $this->assertSame('alice.test', $didDoc->handle());
        $this->assertSame('https://pds', $didDoc->pdsUrl());
        $this->assertSame([], $didDoc->get('verificationMethod'));
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
}
