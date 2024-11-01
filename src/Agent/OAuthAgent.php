<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Agent;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\Token;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Enums\Bsky;
use Revolution\Bluesky\Events\DPoPNonceReceived;
use Revolution\Bluesky\Events\OAuthSessionRefreshing;
use Revolution\Bluesky\Events\OAuthSessionUpdated;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Socalite\Key\DPoP;
use Revolution\Bluesky\Support\Identity;

/**
 * OAuth based agent.
 */
final class OAuthAgent implements Agent
{
    use Macroable;
    use Conditionable;

    public function __construct(
        #[\SensitiveParameter]
        protected OAuthSession $session,
    ) {
    }

    public static function create(#[\SensitiveParameter] OAuthSession $session): self
    {
        return new self($session);
    }

    /**
     * @throws AuthenticationException
     */
    public function http(bool $auth = true): PendingRequest
    {
        if ($auth && $this->tokenExpired()) {
            $this->refreshSession();
        }

        return Http::baseUrl($this->baseUrl($auth))
            ->withToken($this->token(), 'DPoP')
            ->withRequestMiddleware($this->apiRequestMiddleware(...))
            ->withResponseMiddleware($this->apiResponseMiddleware(...))
            ->retry(times: 2, throw: false);
    }

    protected function apiRequestMiddleware(RequestInterface $request): RequestInterface
    {
        $dpop_proof = DPoP::apiProof(
            jwk: DPoP::load(),
            iss: $this->session()->issuer(default: Bluesky::entryway()),
            url: (string) $request->getUri(),
            token: $this->token(),
            nonce: $this->session(DPoP::API_NONCE, ''),
            method: $request->getMethod(),
        );

        return $request->withHeader('DPoP', $dpop_proof);
    }

    protected function apiResponseMiddleware(ResponseInterface $response): ResponseInterface
    {
        $dpop_nonce = (string) collect($response->getHeader('DPoP-Nonce'))->first();

        $this->session->put(DPoP::API_NONCE, $dpop_nonce);

        DPoPNonceReceived::dispatch($dpop_nonce, $this->session);

        OAuthSessionUpdated::dispatch($this->session);

        return $response;
    }

    /**
     * @throws AuthenticationException
     */
    public function refreshSession(): self
    {
        if (empty($refresh = $this->session()->refresh())) {
            throw new AuthenticationException();
        }

        // Since refresh_token can only be used once, delete it here.
        // If an error occurs while refreshing the token, you will
        // need to re-authenticate and re-obtain refresh_token.
        $this->session->forget('refresh_token');
        OAuthSessionRefreshing::dispatch($this->session);

        /** @var Token $token */
        $token = Socialite::driver('bluesky')
            ->issuer($this->session()->issuer(default: Bsky::Entryway->value))
            ->refreshToken($refresh);

        // When refresh is successful, new refresh_token here.
        $this->session = Socialite::driver('bluesky')->getOAuthSession();
        $this->session->put('access_token', $token->token);
        $this->session->put('refresh_token', $token->refreshToken);
        $this->session->put('expires_in', $token->expiresIn);

        $this->refreshProfile($this->did());

        OAuthSessionUpdated::dispatch($this->session);

        return $this;
    }

    public function refreshProfile(string $did): self
    {
        if (empty($did) || ! Identity::isDID($did)) {
            return $this;
        }

        $this->session->put('didDoc', Bluesky::identity()->resolveDID($did)->json());
        $this->session->put('profile', Bluesky::profile($did)->json());

        if (! $this->session->has('iss') && ! empty($pds_url = $this->pdsUrl())) {
            $pds = Bluesky::pds()->resource($pds_url);

            $this->session->put('pds', $pds->toArray());
            $this->session->put('iss', $pds->authServer());
        }

        return $this;
    }

    /**
     * @return ($key is non-empty-string ? mixed : OAuthSession)
     */
    public function session(?string $key = null, $default = null): mixed
    {
        return empty($key) ? $this->session : $this->session->get($key, $default);
    }

    public function did(string $default = ''): string
    {
        return $this->session->did($default);
    }

    public function token(string $default = ''): string
    {
        return $this->session->token($default);
    }

    public function refresh(string $default = ''): string
    {
        return $this->session->refresh($default);
    }

    public function tokenExpired(): bool
    {
        return $this->session()->tokenExpired();
    }

    public function pdsUrl(?string $default = null): ?string
    {
        return $this->session->didDoc()->pdsUrl($default);
    }

    public function baseUrl(bool $auth = true): string
    {
        $base = $this->pdsUrl();

        if (empty($base)) {
            if ($auth) {
                logger()->warning('If you get an error on the public endpoint, please authenticate.');
            }

            $base = Bsky::PublicEndpoint->value;
        } else {
            $base .= '/xrpc/';
        }

        return $base;
    }
}
