<?php

declare(strict_types=1);

namespace Tests\Feature\Agent;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\Token;
use Mockery as m;
use Revolution\Bluesky\Agent\OAuthAgent;
use Revolution\Bluesky\Crypto\JsonWebToken;
use Revolution\Bluesky\Events\OAuthSessionUpdated;
use Revolution\Bluesky\Events\RefreshTokenReplayed;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Socialite\Key\OAuthKey;
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

    public function test_instance(): void
    {
        $session = new OAuthSession([]);
        $agent = new OAuthAgent($session);

        $this->assertInstanceOf(OAuthAgent::class, $agent);
    }

    public function test_agent(): void
    {
        $agent = Bluesky::withToken(OAuthSession::create(['refresh_token' => 'test']))->agent();

        $this->assertInstanceOf(OAuthAgent::class, $agent);
    }

    public function test_http(): void
    {
        $session = new OAuthSession([
            'iss' => 'iss',
            'expires_in' => 3600,
            'access_token' => JsonWebToken::encode(
                [],
                [
                    'exp' => now()->addSeconds(3600)->timestamp,
                ],
                OAuthKey::load()->privatePEM(),
            ),
        ]);
        $agent = new OAuthAgent($session);

        $http = $agent->http();

        $this->assertInstanceOf(PendingRequest::class, $http);
    }

    public function test_profile(): void
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

        $response = Bluesky::getProfile('did');

        $this->assertTrue($response->successful());
    }

    public function test_refresh_session(): void
    {
        Event::fake();

        $session = OAuthSession::create([
            'refresh_token' => 'refresh_token',
        ]);

        Socialite::shouldReceive('driver->issuer->refreshToken')->andReturn(new Token('access_token', 'refresh_token', 1, []));
        Socialite::shouldReceive('driver->getOAuthSession')->andReturn(new OAuthSession(['did' => 'did:plc:test']));

        Bluesky::shouldReceive('entryway')->andReturn('https://iss');
        Bluesky::shouldReceive('identity->resolveDID->json')->andReturn([
            'service' => [['id' => '#atproto_pds', 'serviceEndpoint' => 'https://pds']],
        ]);
        Bluesky::shouldReceive('getProfile->json')->once()->andReturn([
            'handle' => 'handle',
        ]);
        Bluesky::shouldReceive('pds->getProtectedResource')->once()->andReturn(ProtectedResource::make([
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

    public function test_refresh_session_replayed(): void
    {
        Event::fake();

        $this->expectException(AuthenticationException::class);

        $session = OAuthSession::create([
            'refresh_token' => 'refresh_token',
        ]);

        Http::fakeSequence()
            ->push([
                'token_endpoint' => 'http://localhost/oauth/token',
            ])
            ->whenEmpty(Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'refresh token replayed',
            ], 400));

        $agent = new OAuthAgent($session);
        $agent->refreshSession();

        Http::assertSentCount(2);
        Event::assertDispatched(RefreshTokenReplayed::class);
        Event::assertNotDispatched(OAuthSessionUpdated::class);
    }

    public function test_token_expired(): void
    {
        $session = OAuthSession::create([
            'token_created_at' => now()->toISOString(),
            'expires_in' => 3600,
            'access_token' => JsonWebToken::encode(
                [],
                [
                    'exp' => now()->addSeconds(3600)->timestamp,
                ],
                OAuthKey::load()->privatePEM(),
            ),
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

    public function test_pds_url(): void
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
