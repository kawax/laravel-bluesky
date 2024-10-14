<?php

declare(strict_types=1);

namespace Tests\Feature\Client;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Revolution\Bluesky\BlueskyClient;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Notifications\BlueskyMessage;
use Tests\TestCase;

class ClientTest extends TestCase
{
    public function test_login()
    {
        Http::fake(fn () => ['accessJwt' => 'test', 'did' => 'test']);

        $client = new BlueskyClient();

        $client->service('https://bsky.social')
            ->login(identifier: 'identifier', password: 'password');

        Http::assertSent(function (Request $request) {
            return $request['identifier'] === 'identifier';
        });

        $this->assertSame('test', $client->session('accessJwt'));
        $this->assertTrue($client->check());
    }

    public function test_logout()
    {
        Http::fake(fn () => ['accessJwt' => 'test', 'did' => 'test']);

        $client = new BlueskyClient();

        $client->service('https://bsky.social')
            ->login(identifier: 'identifier', password: 'password');

        Http::assertSent(function (Request $request) {
            return $request['identifier'] === 'identifier';
        });

        $client->logout();

        $this->assertNull($client->session());
        $this->assertFalse($client->check());
    }

    public function test_session()
    {
        Http::fake(fn () => ['accessJwt' => 'test', 'did' => 'test']);

        $client = new BlueskyClient();

        $client->service('https://bsky.social')
            ->login(identifier: 'identifier', password: 'password');

        $this->assertSame(['accessJwt' => 'test', 'did' => 'test'], $client->session()->toArray());
        $this->assertSame('test', $client->session('accessJwt'));
    }

    public function test_feed()
    {
        Http::fakeSequence()
            ->push(['accessJwt' => 'test', 'did' => 'test'])
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
            ->push(['accessJwt' => 'test', 'did' => 'test'])
            ->push(['feed' => ['post' => []]]);

        $response = Bluesky::unless(Bluesky::check(), fn () => Bluesky::login(identifier: 'identifier', password: 'password'))
            ->timeline(limit: 10, cursor: '2024');

        $this->assertTrue($response->collect()->has('feed'));
    }

    public function test_post()
    {
        Http::fakeSequence()
            ->push(['accessJwt' => 'test', 'did' => 'test'])
            ->push(['uri' => 'at']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->post(text: 'test');

        $this->assertTrue($response->collect()->has('uri'));
    }

    public function test_post_message()
    {
        Http::fakeSequence()
            ->push(['accessJwt' => 'test', 'did' => 'test'])
            ->push(['uri' => 'at']);

        $m = BlueskyMessage::create('text');

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->post(text: $m);

        $this->assertTrue($response->collect()->has('uri'));
    }

    public function test_upload_blob()
    {
        Http::fakeSequence()
            ->push(['accessJwt' => 'test', 'did' => 'test'])
            ->push(['blob' => '...']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->uploadBlob('test', 'image/png');

        $this->assertTrue($response->collect()->has('blob'));
    }

    public function test_resolve_handle()
    {
        Http::fakeSequence()
            ->push(['accessJwt' => 'test', 'did' => 'test'])
            ->push(['did' => 'test']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->resolveHandle(handle: 'alice.test');

        $this->assertTrue($response->collect()->has('did'));
        $this->assertSame('test', $response->json('did'));
    }

    public function test_refresh_session()
    {
        Http::fakeSequence()
            ->push(['accessJwt' => 'test', 'refreshJwt' => 'test', 'did' => 'test'])
            ->push(['accessJwt' => 'test', 'refreshJwt' => 'test', 'did' => 'test']);

        Bluesky::login(identifier: 'identifier', password: 'password')
            ->refreshSession();

        $this->assertSame('test', Bluesky::session('refreshJwt'));
    }

    public function test_with_session()
    {
        Bluesky::withSession(['accessJwt' => 'test', 'refreshJwt' => 'test', 'did' => 'test']);

        $this->assertSame('test', Bluesky::session('did'));
    }

    public function test_resolve_did_plc()
    {
        Http::fakeSequence()
            ->push(['id' => 'did:plc:test']);

        $response = Bluesky::resolveDID(did: 'did:plc:test');

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

        $response = Bluesky::resolveDID(did: 'did:web:localhost');

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

        $response = Bluesky::resolveDID(did: 'did:test:test');

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

        $response = Bluesky::resolveDID(did: 'did:test');

        Http::assertNothingSent();
    }
}
