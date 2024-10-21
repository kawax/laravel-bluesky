<?php

declare(strict_types=1);

namespace Tests\Feature\Client;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Mockery\MockInterface;
use Revolution\Bluesky\Agent\OAuthAgent;
use Revolution\Bluesky\BlueskyClient;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Notifications\BlueskyMessage;
use Revolution\Bluesky\Session\LegacySession;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Support\DNS;
use Revolution\Bluesky\Support\Identity;
use Tests\TestCase;
use Mockery as m;

class ClientTest extends TestCase
{
    protected array $session = ['accessJwt' => 'test', 'refreshJwt' => 'test', 'did' => 'test', 'handle' => 'handle'];

    public function test_login()
    {
        Http::fake(fn () => $this->session);

        $client = new BlueskyClient();

        $client->login(identifier: 'identifier', password: 'password');

        Http::assertSent(function (Request $request) {
            return $request['identifier'] === 'identifier';
        });

        $this->assertSame('test', $client->agent()->session('accessJwt'));
        $this->assertTrue($client->check());
    }

    public function test_logout()
    {
        Http::fake(fn () => $this->session);

        $client = new BlueskyClient();

        $client->login(identifier: 'identifier', password: 'password');

        Http::assertSent(function (Request $request) {
            return $request['identifier'] === 'identifier';
        });

        $client->logout();

        $this->assertNull($client->agent());
        $this->assertFalse($client->check());
    }

    public function test_session()
    {
        Http::fake(fn () => $this->session);

        $client = new BlueskyClient();

        $client->login(identifier: 'identifier', password: 'password');

        $this->assertIsArray($client->agent()->session());
        $this->assertSame('test', $client->agent()->session('accessJwt'));
    }

    public function test_feed()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['feed' => ['post' => []]]);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->when(Bluesky::check(), function () {
                return Bluesky::feed(limit: 10, cursor: '2024', filter: 'posts_with_media');
            });

        $this->assertTrue($response->collect()->has('feed'));
    }

    public function test_timeline()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['feed' => ['post' => []]]);

        $response = Bluesky::unless(Bluesky::check(), fn () => Bluesky::login(identifier: 'identifier', password: 'password'))
            ->timeline(limit: 10, cursor: '2024');

        $this->assertTrue($response->collect()->has('feed'));
    }

    public function test_post()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['uri' => 'at']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->post(text: 'test');

        $this->assertTrue($response->collect()->has('uri'));
    }

    public function test_post_message()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['uri' => 'at']);

        $m = BlueskyMessage::create('text');

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->post(text: $m);

        $this->assertTrue($response->collect()->has('uri'));
    }

    public function test_upload_blob()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['blob' => '...']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->uploadBlob('test', 'image/png');

        $this->assertTrue($response->collect()->has('blob'));
    }

    public function test_resolve_handle()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['did' => 'test']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->resolveHandle(handle: 'alice.test');

        $this->assertTrue($response->collect()->has('did'));
        $this->assertSame('test', $response->json('did'));
    }

    public function test_get_profile()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['did' => 'test']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->profile(actor: 'test');

        $this->assertTrue($response->collect()->has('did'));
        $this->assertSame('test', $response->json('did'));
    }

    public function test_resolve_did_plc()
    {
        Http::fakeSequence()
            ->push(['id' => 'did:plc:test']);

        $response = (new Identity())->resolveDID(did: 'did:plc:test');

        $this->assertTrue($response->collect()->has('id'));
        $this->assertSame('did:plc:test', $response->json('id'));

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://plc.directory/did:plc:test';
        });
    }

    public function test_resolve_did_web()
    {
        Http::fakeSequence()
            ->push(['id' => 'did:web:localhost']);

        $response = Bluesky::identity()->resolveDID(did: 'did:web:localhost');

        $this->assertTrue($response->collect()->has('id'));
        $this->assertSame('did:web:localhost', $response->json('id'));

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://localhost/.well-known/did.json';
        });
    }

    public function test_resolve_did_unsupported()
    {
        $this->expectException(InvalidArgumentException::class);

        Http::fake();

        $response = Bluesky::identity()->resolveDID(did: 'did:test:test');

        Http::assertNothingSent();
    }

    public function test_resolve_handle_invalid()
    {
        $this->expectException(InvalidArgumentException::class);

        Http::fake();

        $response = Bluesky::resolveHandle(handle: 'invalid');

        Http::assertNothingSent();
    }

    public function test_resolve_did_invalid()
    {
        $this->expectException(InvalidArgumentException::class);

        Http::fake();

        $response = Bluesky::identity()->resolveDID(did: 'did:test');

        Http::assertNothingSent();
    }

    public function test_identity_resolve_handle_dns()
    {
        $this->mock(DNS::class, function (MockInterface $mock) {
            $mock->shouldReceive('record')->andReturn([
                [
                    'txt' => 'did=did:plc:1234',
                ],
            ]);
        });

        $did = Bluesky::identity()->resolveHandle('example.com');

        $this->assertSame('did:plc:1234', $did);
    }

    public function test_identity_resolve_handle_wellknown()
    {
        $this->mock(DNS::class, function (MockInterface $mock) {
            $mock->shouldReceive('record')->andReturn([]);
        });

        Http::fakeSequence()
            ->push('did:plc:1234');

        $did = Bluesky::identity()->resolveHandle('example.com');

        $this->assertSame('did:plc:1234', $did);
    }

    public function test_identity_resolve_identity_handle()
    {
        $this->mock(DNS::class, function (MockInterface $mock) {
            $mock->shouldReceive('record')->andReturn([
                [
                    'txt' => 'did=did:web:example.com',
                ],
            ]);
        });

        Http::fakeSequence()
            ->push(['id' => 'did:web:example.com']);

        $response = Bluesky::identity()->resolveIdentity('example.com');

        $this->assertTrue($response->collect()->has('id'));
        $this->assertSame('did:web:example.com', $response->json('id'));

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://example.com/.well-known/did.json';
        });
    }

    public function test_identity_resolve_identity_did()
    {
        Http::fakeSequence()
            ->push(['id' => 'did:web:example.com']);

        $response = Bluesky::identity()->resolveIdentity('did:web:example.com');

        $this->assertTrue($response->collect()->has('id'));
        $this->assertSame('did:web:example.com', $response->json('id'));

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://example.com/.well-known/did.json';
        });
    }

    public function test_with_agent()
    {
        $agent = Bluesky::withAgent(OAuthAgent::create(OAuthSession::create()))
            ->agent();

        $this->assertInstanceOf(OAuthAgent::class, $agent);
    }

    public function test_client_identity()
    {
        $this->assertInstanceOf(Identity::class, Bluesky::identity());
    }

    public function test_legacy_session()
    {
        $session = new LegacySession($this->session);

        $this->assertArrayHasKey('accessJwt', $session->toArray());
        $this->assertSame('test', $session->toArray()['accessJwt']);
    }

    public function test_oauth_session()
    {
        $oauth = [
            'access_token' => 'test',
            'refresh_token' => 'test',
            'did' => 'test',
            'handle' => 'handle',
        ];

        $session = new OAuthSession($oauth);

        $this->assertArrayHasKey('access_token', $session->toArray());
        $this->assertSame('test', $session->toArray()['access_token']);
    }

    public function test_with_oauth()
    {
        $oauth = [
            'access_token' => 'test',
            'refresh_token' => 'test',
            'did' => 'test',
            'handle' => 'handle',
        ];

        $session = OAuthSession::create($oauth);

        $client = Bluesky::withToken($session);

        $this->assertInstanceOf(OAuthAgent::class, $client->agent());
        $this->assertSame('test', $client->agent()->did());
    }
}
