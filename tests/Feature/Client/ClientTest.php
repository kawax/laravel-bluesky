<?php

declare(strict_types=1);

namespace Tests\Feature\Client;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Revolution\Bluesky\BlueskyClient;
use Revolution\Bluesky\Facades\Bluesky;
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
            ->feed(limit: 10, cursor: '2024', filter: 'posts_with_media');

        $this->assertTrue($response->collect()->has('feed'));
    }

    public function test_timeline()
    {
        Http::fakeSequence()
            ->push(['accessJwt' => 'test', 'did' => 'test'])
            ->push(['feed' => ['post' => []]]);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
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
}
