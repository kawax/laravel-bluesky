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

    public function test_feed()
    {
        Http::fakeSequence()
            ->push(['accessJwt' => 'test', 'did' => 'test'])
            ->push(['feed' => ['post' => []]]);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->feed(filter: 'posts_with_replies');

        $this->assertTrue($response->has('feed'));
    }

    public function test_timeline()
    {
        Http::fakeSequence()
            ->push(['accessJwt' => 'test', 'did' => 'test'])
            ->push(['feed' => ['post' => []]]);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->timeline(cursor: '1');

        $this->assertTrue($response->has('feed'));
    }

    public function test_post()
    {
        Http::fakeSequence()
            ->push(['accessJwt' => 'test', 'did' => 'test'])
            ->push(['uri' => 'at']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->post(text: 'test');

        $this->assertTrue($response->has('uri'));
    }
}
