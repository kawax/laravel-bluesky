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
        Event::fake();

        $session = OAuthSession::create([
            'iss' => 'iss',
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
            'token_created_at' => now()->toISOString(),
            'expires_in' => 3600,
        ]);

        Http::fakeSequence()
            ->push(
                body: [
                    'error' => 'error',
                ],
                status: 401,
                headers: [
                    'DPoP-Nonce' => 'nonce',
                ],
            )
            ->push(
                body: [
                    'did' => 'did',
                    'handle' => 'handle',
                ],
                headers: [
                    'DPoP-Nonce' => 'nonce',
                ],
            );

        $response = Bluesky::withToken($session)
            ->profile();

        $this->assertTrue($response->successful());
        $this->assertSame('nonce', $response->header('DPoP-Nonce'));

        Http::assertSent(function (Request $request) {
            return $request->hasHeader('DPoP');
        });

        Event::assertDispatched(OAuthSessionUpdated::class);
    }

    public function test_refresh_token()
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
        Bluesky::shouldReceive('pds->resource')->once()->andReturn([
            'authorization_servers' => ['https://iss'],
        ]);
        Bluesky::shouldReceive('pds->endpoint')->once()->andReturn('https://pds');

        $agent = new OAuthAgent($session);
        $agent->refreshToken();

        $this->assertSame('access_token', $agent->token());
        $this->assertSame('refresh_token', $agent->session('refresh_token'));
        $this->assertSame('handle', $agent->handle());
        $this->assertSame('https://iss', $agent->session('iss'));

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
}
