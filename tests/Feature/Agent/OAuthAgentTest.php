<?php

namespace Tests\Feature\Agent;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\Token;
use Mockery as m;
use Illuminate\Support\Facades\Http;
use Revolution\Bluesky\Agent\OAuthAgent;
use Revolution\Bluesky\Events\OAuthSessionUpdated;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Support\ProtectedResource;
use Tests\TestCase;

class OAuthAgentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function test_instance()
    {
        $session = new OAuthSession([]);
        $agent = new OAuthAgent($session);

        $this->assertInstanceOf(OAuthAgent::class, $agent);
    }

    public function test_agent()
    {
        $agent = Bluesky::withToken(OAuthSession::create(['refresh_token' => 'test']))->agent();

        $this->assertInstanceOf(OAuthAgent::class, $agent);
    }

    public function test_http()
    {
        $session = new OAuthSession([
            'iss' => 'iss',
            'token_created_at' => now()->toISOString(),
            'expires_in' => 3600,
        ]);
        $agent = new OAuthAgent($session);

        $http = $agent->http();

        $this->assertInstanceOf(PendingRequest::class, $http);
    }

    public function test_profile()
    {
        Http::fakeSequence()
            ->push(
                body: [
                    'did' => 'did',
                    'handle' => 'handle',
                ],
                headers: [
                    'DPoP-Nonce' => 'nonce',
                ],
            );

        $response = Bluesky::profile();

        $this->assertTrue($response->successful());}

    public function test_refresh_session()
    {
        Event::fake();

        $session = OAuthSession::create([
            'refresh_token' => 'refresh_token',
        ]);

        Socialite::shouldReceive('driver->issuer->refreshToken')->andReturn(new Token('access_token', 'refresh_token', 1, []));
        Socialite::shouldReceive('driver->getOAuthSession')->andReturn(new OAuthSession(['did' => 'did:plc:test']));

        Bluesky::shouldReceive('identity->resolveDID->json')->andReturn([
            'service' => [['id' => '#atproto_pds', 'serviceEndpoint' => 'https://pds']],
        ]);
        Bluesky::shouldReceive('profile->json')->once()->andReturn([
            'handle' => 'handle',
        ]);
        Bluesky::shouldReceive('pds->resource')->once()->andReturn(ProtectedResource::create([
            'authorization_servers' => ['https://iss'],
        ]));

        $agent = new OAuthAgent($session);
        $agent->refreshSession();

        $this->assertSame('access_token', $agent->session()->token());
        $this->assertSame('refresh_token', $agent->session()->refresh());
        $this->assertSame('handle', $agent->session()->handle());
        $this->assertSame('https://iss', $agent->session()->issuer());

        Event::assertDispatched(OAuthSessionUpdated::class);
    }

    public function test_token_expired()
    {
        $session = OAuthSession::create([
            'token_created_at' => now()->toISOString(),
            'expires_in' => 3600,
        ]);

        $agent = OAuthAgent::create($session);

        $this->travel(100)->seconds();

        $this->assertFalse($agent->tokenExpired());

        $this->travelBack();

        $this->travel(3700)->seconds();

        $this->assertTrue($agent->tokenExpired());

        $this->travelBack();

        $agent = OAuthAgent::create(OAuthSession::create());

        $this->assertTrue($agent->tokenExpired());
    }

    public function test_pds_url()
    {
        $session = OAuthSession::create([
            'didDoc' => [
                'service' => [
                    [
                        'id' => '#atproto_pds',
                        'serviceEndpoint' => 'https://pds',
                    ],
                ],
            ],
        ]);

        $agent = OAuthAgent::create($session);

        $this->assertSame('https://pds', $agent->pdsUrl());
        $this->assertSame('https://pds/xrpc/', $agent->baseUrl());
    }
}
