<?php

namespace Revolution\Bluesky\Socalite;

use Illuminate\Support\Str;
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
use InvalidArgumentException;

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
            throw new InvalidArgumentException('Bluesky requires PKCE.');
        }

        $this->updateServiceWithHint();

        $par_request_uri = $this->getParRequestUrl($state);

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
            throw new InvalidArgumentException('Invalid Issuer.');
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $user = $this->getUserWithSession($response);

        $this->clearSession();

        return $this->userInstance($response, $user);
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
            'id' => data_get($user, 'did'),
            'nickname' => data_get($user, 'handle'),
            'name' => data_get($user, 'displayName'),
            'avatar' => data_get($user, 'avatar'),
            'session' => data_get($user, 'session'),
        ]);
    }

    protected function hasInvalidIssuer(): bool
    {
        return $this->authServerMeta('issuer') !== $this->request->input('iss');
    }

    /**
     * Set service/auth server/issuer. e.g. "bsky.social"
     */
    public function service(string $service): self
    {
        $this->service = Str::chopStart($service, ['https://', 'http://']);

        return $this;
    }

    /**
     * Set service/auth server/issuer. e.g. "bsky.social"
     */
    public function issuer(string $iss): self
    {
        return $this->service($iss);
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
