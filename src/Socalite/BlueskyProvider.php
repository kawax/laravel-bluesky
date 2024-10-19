<?php

namespace Revolution\Bluesky\Socalite;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;
use RuntimeException;

class BlueskyProvider extends AbstractProvider implements ProviderInterface
{
    use WithPAR;

    protected string $service = 'bsky.social';

    protected ?string $login_hint = null;

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [
        'atproto',
        'transition:generic',
    ];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * Indicates if PKCE should be used.
     *
     * @var bool
     */
    protected $usesPKCE = true;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        $auth_url = $this->endpoint();

        $meta = $this->getServerMeta($auth_url);
        $this->request->session()->put('bluesky.meta', $meta);

        $par_request_uri = $this->getParRequestUrl(
            auth_url: $auth_url,
            meta: $meta,
            state: $state,
        );

        $authorize_url = Arr::get($meta, 'authorization_endpoint', 'https://bsky.social/oauth/authorize');

        return $authorize_url.'?'.http_build_query([
                'client_id' => $this->clientId,
                'request_uri' => $par_request_uri,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $did = Arr::get($response, 'did', Arr::get($response, 'sub'));

        $user = $this->getUserByToken($did);

        $session = OAuthSession::create(array_merge($user, $response, [
            'iss' => $this->request->input('iss'),
            'dpop_private_key' => $this->request->session()->get('bluesky.dpop_private_key'),
            'dpop_nonce' => $this->request->session()->get('bluesky.dpop_nonce'),
        ]));

        $user['session'] = $session;

        $this->clearSession();

        return $this->userInstance($response, $user);
    }

    /**
     * Get the access token response for the given code.
     *
     * @param  string  $code
     * @return array
     */
    public function getAccessTokenResponse($code): array
    {
        $token_url = $this->getTokenUrl();

        $payload = $this->getTokenFields($code);

        return $this->sendTokenRequest($token_url, $payload);
    }

    protected function sendTokenRequest(string $token_url, array $payload): array
    {
        return Http::withRequestMiddleware(
            function (RequestInterface $request) use ($token_url) {
                $dpop_private_key = $this->request->session()->get('bluesky.dpop_private_key');
                $dpop_private_jwk = DPoP::load($dpop_private_key);

                $dpop_nonce = $this->request->session()->get('bluesky.dpop_nonce', '');

                $dpop_proof = DPop::authProof(
                    jwk: $dpop_private_jwk,
                    url: $token_url,
                    nonce: $dpop_nonce,
                );

                return $request->withHeader('DPoP', $dpop_proof);
            })->withResponseMiddleware(
            function (ResponseInterface $response) {
                $dpop_nonce = collect($response->getHeader('DPoP-Nonce'))->first();

                $this->request->session()->put('bluesky.dpop_nonce', $dpop_nonce);

                return $response;
            })
            ->retry(times: 2, throw: false)
            ->post($token_url, $payload)
            ->json();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        $meta = $this->request->session()->get('bluesky.meta');

        if ($this->request->input('iss') !== Arr::get($meta, 'issuer')) {
            throw new RuntimeException('Invalid iss.');
        }

        return Arr::get($meta, 'token_endpoint', 'https://bsky.social/oauth/token');
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function getTokenFields($code): array
    {
        $fields = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $this->getClientAssertion($this->endpoint()),
        ];

        if ($this->usesPKCE()) {
            $fields['code_verifier'] = $this->request->session()->get('code_verifier');
        }

        return array_merge($fields, $this->parameters);
    }

    protected function getUserByToken($token): array
    {
        $user = Bluesky::identity()->resolveIdentity($token)->collect();

        return $user->merge(Bluesky::profile($token)->json())->toArray();
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id' => Arr::get($user, 'did'),
            'nickname' => Arr::get($user, 'handle'),
            'name' => Arr::get($user, 'displayName'),
            'avatar' => Arr::get($user, 'avatar'),
            'session' => Arr::get($user, 'session'),
        ]);
    }

    /**
     * Get the refresh token response for the given refresh token.
     *
     * @param  string  $refreshToken
     * @return array
     */
    protected function getRefreshTokenResponse($refreshToken): array
    {
        $auth_url = $this->endpoint();

        $meta = $this->getServerMeta($auth_url);
        $this->request->session()->put('bluesky.meta', $meta);

        $token_url = $this->getTokenUrl();

        $payload = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $this->getClientAssertion($this->endpoint()),
        ];

        return $this->sendTokenRequest($token_url, $payload);
    }

    public function service(string $service): self
    {
        $this->service = $service;

        return $this;
    }

    public function hint(?string $login = null): self
    {
        $this->login_hint = $login;

        return $this;
    }

    protected function endpoint(string $path = ''): string
    {
        return "https://$this->service$path";
    }

    protected function clearSession(): void
    {
        $this->request->session()->forget([
            'code_verifier',
//            'bluesky.meta',
//            'bluesky.dpop_private_key',
//            'bluesky.dpop_nonce',
        ]);
    }
}
