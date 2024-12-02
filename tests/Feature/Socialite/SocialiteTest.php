<?php

namespace Tests\Feature\Socialite;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Mockery as m;
use Revolution\Bluesky\Events\DPoPNonceReceived;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Socialite\BlueskyProvider;
use Revolution\Bluesky\Socialite\Key\BlueskyKey;
use Revolution\Bluesky\Socialite\Key\JsonWebKey;
use Revolution\Bluesky\Socialite\Key\JsonWebKeySet;
use Revolution\Bluesky\Socialite\Key\JsonWebToken;
use Revolution\Bluesky\Socialite\OAuthConfig;
use Tests\TestCase;

class SocialiteTest extends TestCase
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
        $provider = Socialite::driver('bluesky');

        $this->assertInstanceOf(BlueskyProvider::class, $provider);
    }

    public function test_redirect()
    {
        $session = app('Illuminate\Contracts\Session\Session');

        $request = Request::create(uri: 'redirect');
        $request->setLaravelSession($session);

        Http::fake([
            'localhost/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://iss',
                'pushed_authorization_request_endpoint' => 'https://par/par',
                'authorization_endpoint' => 'https://authorize/oauth/authorize',
            ]),
            'par/*' => Http::response([
                'request_uri' => 'httsp://request_uri',
            ]),
        ]);

        $provider = new BlueskyProvider($request, 'client_id', 'client_secret', 'redirect');
        $provider->service('localhost');

        $response = $provider->hint('login_hint')->redirect();

        $this->assertStringStartsWith('https://authorize/', $response->getTargetUrl());
        $this->assertStringContainsString(rawurlencode('httsp://request_uri'), $response->getTargetUrl());
    }

    public function test_redirect_login_hint()
    {
        $session = app('Illuminate\Contracts\Session\Session');

        $request = Request::create(uri: 'redirect');
        $request->setLaravelSession($session);

        Http::fake([
            'localhost/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://iss',
                'pushed_authorization_request_endpoint' => 'https://par/par',
                'authorization_endpoint' => 'https://authorize/oauth/authorize',
            ]),
            'par/*' => Http::response([
                'request_uri' => 'httsp://request_uri',
            ]),
            'pds/*' => Http::response([
                'resource' => 'https://pds',
                'authorization_servers' => ['https://localhost'],
            ]),
        ]);

        $provider = new BlueskyProvider($request, 'client_id', 'client_secret', 'redirect');
        $provider->service('localhost');

        $response = $provider->hint('https://pds')->redirect();

        $this->assertStringStartsWith('https://authorize/', $response->getTargetUrl());
        $this->assertStringContainsString(rawurlencode('httsp://request_uri'), $response->getTargetUrl());
    }

    public function test_user()
    {
        $session = app('Illuminate\Contracts\Session\Session');
        $session->put('state', 'state');

        $request = Request::create(uri: 'callback', parameters: [
            'code' => 'code',
            'state' => 'state',
            'iss' => 'https://iss',
        ]);
        $request->setLaravelSession($session);

        Bluesky::partialMock();

        Bluesky::shouldReceive('identity->resolveDID->json')->andReturn([
            'service' => [['id' => '#atproto_pds', 'serviceEndpoint' => 'https://pds']],
        ]);
        Bluesky::shouldReceive('getProfile->json')->andReturn([
            'did' => 'did',
            'handle' => 'handle',
        ]);

        Http::fake([
            'localhost/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://iss',
                'token_endpoint' => 'https://token/oauth/token',
            ]),
            'token/*' => Http::response([
                'sub' => 'did:plc:test',
                'handle' => 'handle',
                'access_token' => 'access_token',
                'refresh_token' => 'refresh_token',
            ]),
            'pds/*' => Http::response([
                'resource' => 'https://pds',
                'authorization_servers' => ['https://localhost'],
            ]),
        ]);

        $provider = new BlueskyProvider($request, 'client_id', 'client_secret', 'redirect');
        $provider->issuer('localhost');

        $user = $provider->user();

        $this->assertSame('did', $user->id);
        $this->assertSame('handle', $user->nickname);
        $this->assertSame('access_token', $user->token);
    }

    public function test_user_login_hint()
    {
        $session = app('Illuminate\Contracts\Session\Session');
        $session->put('state', 'state');

        $request = Request::create(uri: 'callback', parameters: [
            'code' => 'code',
            'state' => 'state',
            'iss' => 'https://iss',
        ]);
        $request->setLaravelSession($session);

        Bluesky::partialMock();

        Bluesky::shouldReceive('identity->resolveIdentity->collect')->andReturn(collect([
            'service' => [['id' => '#atproto_pds', 'serviceEndpoint' => 'https://pds']],
        ]));

        Bluesky::shouldReceive('identity->resolveDID->json')->andReturn([
            'service' => [['id' => '#atproto_pds', 'serviceEndpoint' => 'https://pds']],
        ]);
        Bluesky::shouldReceive('getProfile->json')->andReturn([
            'did' => 'did',
            'handle' => 'handle',
        ]);

        Http::fake([
            'localhost/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://iss',
                'token_endpoint' => 'https://token/oauth/token',
            ]),
            'token/*' => Http::response([
                'sub' => 'did:plc:test',
                'handle' => 'handle',
                'access_token' => 'access_token',
                'refresh_token' => 'refresh_token',
            ]),
            'pds/*' => Http::response([
                'resource' => 'https://pds',
                'authorization_servers' => ['https://localhost'],
            ]),
        ]);

        $provider = new BlueskyProvider($request, 'client_id', 'client_secret', 'redirect');
        $provider->hint('did:plc:test');

        $user = $provider->user();

        $this->assertSame('did', $user->id);
        $this->assertSame('handle', $user->nickname);
        $this->assertSame('access_token', $user->token);
    }

    public function test_user_login_hint_handle()
    {
        $session = app('Illuminate\Contracts\Session\Session');
        $session->put('state', 'state');

        $request = Request::create(uri: 'callback', parameters: [
            'code' => 'code',
            'state' => 'state',
            'iss' => 'https://iss',
        ]);
        $request->setLaravelSession($session);

        Bluesky::partialMock();

        Bluesky::shouldReceive('identity->resolveIdentity->collect')->andReturn(collect([
            'service' => [['id' => '#atproto_pds', 'serviceEndpoint' => 'https://pds']],
        ]));

        Bluesky::shouldReceive('identity->resolveDID->json')->andReturn([
            'service' => [['id' => '#atproto_pds', 'serviceEndpoint' => 'https://pds']],
        ]);

        Bluesky::shouldReceive('resolveHandle->json')->andReturn('did:plc:test');

        Bluesky::shouldReceive('getProfile->json')->andReturn([
            'did' => 'did',
            'handle' => 'handle',
        ]);

        Http::fake([
            'localhost/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://iss',
                'token_endpoint' => 'https://token/oauth/token',
            ]),
            'token/*' => Http::response([
                'sub' => 'did:plc:test',
                'handle' => 'handle',
                'access_token' => 'access_token',
                'refresh_token' => 'refresh_token',
            ]),
            'pds/*' => Http::response([
                'resource' => 'https://pds',
                'authorization_servers' => ['https://localhost'],
            ]),
        ]);

        $provider = new BlueskyProvider($request, 'client_id', 'client_secret', 'redirect');
        $provider->hint('alice.test');

        $user = $provider->user();

        $this->assertSame('did', $user->id);
        $this->assertSame('handle', $user->nickname);
        $this->assertSame('access_token', $user->token);
    }

    public function test_refresh()
    {
        $session = app('Illuminate\Contracts\Session\Session');

        $request = Request::create(uri: 'refresh');
        $request->setLaravelSession($session);

        Event::fake();

        Http::fake([
            'localhost/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://iss',
                'authorization_endpoint' => 'https://authorize/oauth/authorize',
                'token_endpoint' => 'https://token/oauth/token',
            ]),
            'token/*' => Http::response([
                'did' => 'did:plc:test',
                'handle' => 'handle',
                'access_token' => 'access_token',
                'refresh_token' => 'refresh_token',
                'expires_in' => 3600,
            ]),
            'pds/*' => Http::response([
                'resource' => 'https://pds',
                'authorization_servers' => ['https://localhost'],
            ]),
        ]);

        $provider = new BlueskyProvider($request, 'client_id', 'client_secret', 'redirect');
        $provider->issuer(iss: 'localhost')
            ->setOAuthSession(OAuthSession::create());

        $token = $provider->refreshToken('refresh_token');

        $this->assertSame('access_token', $token->token);
        $this->assertSame('refresh_token', $token->refreshToken);
        $this->assertSame('refresh_token', $token->refreshToken);

        Event::assertDispatched(DPoPNonceReceived::class);
    }

    public function test_jwk_private()
    {
        $jwk = new JsonWebKey(BlueskyKey::create()->privateKey());
        $jwk->withKid('kid');

        $this->assertArrayHasKey('d', $jwk->toArray());
        $this->assertSame('kid', $jwk->kid());
        $this->assertIsString((string) $jwk);
    }

    public function test_jwk_public()
    {
        $jwk = new JsonWebKey(BlueskyKey::create()->publicKey());
        $jwk->withKid('kid')->asPublic();

        $this->assertArrayNotHasKey('d', $jwk->toArray());
        $this->assertSame('kid', $jwk->kid());
        $this->assertIsString((string) $jwk);
    }

    public function test_jwks()
    {
        $jwks = JsonWebKeySet::load();

        $this->assertArrayHasKey('keys', $jwks->toArray());
        $this->assertIsString((string) $jwks);
    }

    public function test_route_client_meta()
    {
        $response = $this->get(route('bluesky.oauth.client-metadata'));

        $response->assertOk();
    }

    public function test_route_client_meta_using()
    {
        OAuthConfig::clientMetadataUsing(function () {
            return ['client_id' => 'test'];
        });

        $response = $this->get(route('bluesky.oauth.client-metadata'));

        $response->assertOk()
            ->assertJson(['client_id' => 'test']);
    }

    public function test_route_jwks()
    {
        $response = $this->get(route('bluesky.oauth.jwks'));

        $response->assertOk();
    }

    public function test_route_jwks_using()
    {
        OAuthConfig::jwksUsing(function () {
            return ['keys' => 'test'];
        });

        $response = $this->get(route('bluesky.oauth.jwks'));

        $response->assertOk()
            ->assertJson(['keys' => 'test']);
    }

    public function test_jwt()
    {
        $jwtStr = JsonWebToken::encode(
            head: ['typ' => 'JWT', 'alg' => JsonWebKey::ALG],
            payload: [
                'iss' => 'iss',
            ],
            key: BlueskyKey::create()->privateKey(),
        );

        [$header, $payload, $sig] = JsonWebToken::explode($jwtStr);

        $this->assertArrayHasKey('typ', $header);
        $this->assertSame('iss', $payload['iss']);
    }
}
