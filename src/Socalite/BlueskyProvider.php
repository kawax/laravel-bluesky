<?php

namespace Revolution\Bluesky\Socalite;

use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;
use Revolution\Bluesky\Enums\AtProto;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Socalite\Concerns\WithAuthServer;
use Revolution\Bluesky\Socalite\Concerns\WithClientAssertion;
use Revolution\Bluesky\Socalite\Concerns\WithOAuthSession;
use Revolution\Bluesky\Socalite\Concerns\WithPAR;
use Revolution\Bluesky\Socalite\Concerns\WithPDS;
use Revolution\Bluesky\Socalite\Concerns\WithTokenRequest;
use Revolution\Bluesky\Support\Identity;
use RuntimeException;

class BlueskyProvider extends AbstractProvider implements ProviderInterface
{
    use WithAuthServer;
    use WithPAR;
    use WithPDS;
    use WithClientAssertion;
    use WithTokenRequest;
    use WithOAuthSession;

    protected string $service = AtProto::Entryway->value;

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
     * @inheritdoc
     */
    protected function getAuthUrl($state): string
    {
        if ($this->isStateless() || empty($state)) {
            throw new InvalidStateException('Bluesky does not support stateless.');
        }

        if (! $this->usesPKCE()) {
            throw new RuntimeException('Bluesky requires PKCE.');
        }

        $par_request_uri = $this->getParRequestUrl(state: $state);

        $authorize_url = $this->authServerMeta('authorization_endpoint', 'https://bsky.social/oauth/authorize');

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

        if ($this->hasInvalidIssuer()) {
            throw new RuntimeException('Invalid Issuer.');
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $user = $this->getUserWithSession($response);

        $this->clearSession();

        return $this->userInstance($response, $user);
    }

    protected function hasInvalidIssuer(): bool
    {
        return $this->authServerMeta('issuer') !== $this->request->input('iss');
    }

    protected function hasInvalidDID(?string $did): bool
    {
        if (! Identity::isDID($did)) {
            return true;
        }

        if (! empty($this->login_hint)) {
            if (Identity::isDID($this->login_hint)) {
                return $this->login_hint !== $did;
            }
            if (Identity::isHandle($this->login_hint)) {
                return Bluesky::identity()->resolveHandle($this->login_hint) !== $did;
            }
        }

        return false;
    }

    protected function hasInvalidUser(array $user): bool
    {
        $pds_url = Arr::get($user, 'service.0.serviceEndpoint');

        $auth_url = $this->pdsProtectedResourceMeta($pds_url, 'authorization_servers.0');

        return $this->authUrl() !== $auth_url;
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

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return $this->authServerMeta('token_endpoint', 'https://bsky.social/oauth/token');
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
            'client_assertion' => $this->getClientAssertion($this->authUrl()),
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
        $token_url = $this->getTokenUrl();

        $payload = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $this->getClientAssertion($this->authUrl()),
        ];

        return $this->sendTokenRequest($token_url, $payload);
    }

    /**
     * Set service/auth server. e.g. "bsky.social"
     */
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

    protected function authUrl(): string
    {
        return 'https://'.$this->service;
    }

    protected function clearSession(): void
    {
        $this->request->session()->forget([
            'code_verifier',
        ]);
    }
}
