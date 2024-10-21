<?php

namespace Revolution\Bluesky\Agent;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\Token;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Enums\AtProto;
use Revolution\Bluesky\Events\OAuthSessionUpdated;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Socalite\DPoP;
use RuntimeException;

/**
 * OAuth based agent.
 */
class OAuthAgent implements Agent
{
    public function __construct(
        #[\SensitiveParameter]
        protected OAuthSession $session,
    ) {
    }

    public static function create(#[\SensitiveParameter] OAuthSession $session): self
    {
        return new self($session);
    }

    public function http(bool $auth = true): PendingRequest
    {
        return Http::baseUrl($this->baseUrl($auth))
            ->withToken($this->token(), 'DPoP')
            ->withRequestMiddleware(function (RequestInterface $request) {
                $dpop_proof = DPoP::apiProof(
                    jwk: DPoP::load(),
                    iss: $this->session('iss', Bluesky::entryway()),
                    url: $request->getUri(),
                    token: $this->token(),
                    nonce: $this->session(DPoP::API_NONCE, ''),
                    method: $request->getMethod(),
                );

                return $request->withHeader('DPoP', $dpop_proof);
            })->withResponseMiddleware(function (ResponseInterface $response) {
                $dpop_nonce = collect($response->getHeader('DPoP-Nonce'))->first();

                $this->session->put(DPoP::API_NONCE, $dpop_nonce);

                OAuthSessionUpdated::dispatch($this->session);

                return $response;
            })->retry(times: 2, throw: false);
    }

    public function refreshToken(): self
    {
        if (empty($refresh = $this->session->refresh())) {
            throw new RuntimeException('Missing refresh token.');
        }

        /** @var Token $token */
        $token = Socialite::driver('bluesky')
            ->refreshToken($refresh);

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
        $this->session->merge(Bluesky::identity()->resolveDID($did)->collect());

        $this->session->merge(Bluesky::withAgent($this)->profile($did)->collect());

        if (! $this->session->has('iss')) {
            $this->session->put('iss', data_get(Bluesky::pds()->protectedResource($this->pdsUrl()), 'authorization_servers.{first}'));
        }

        return $this;
    }

    public function session(?string $key = null, $default = null): mixed
    {
        return empty($key) ? $this->session->toArray() : $this->session->get($key, $default);
    }

    public function did(): string
    {
        return $this->session->did();
    }

    public function handle(): string
    {
        return $this->session->handle();
    }

    public function token(): string
    {
        return $this->session->token();
    }

    public function pdsUrl(?string $default = null): ?string
    {
        return data_get($this->session->toArray(), 'service.{first}.serviceEndpoint', $default);
    }

    public function baseUrl(bool $auth = true): string
    {
        $base = $this->pdsUrl();

        if (empty($base)) {
            if ($auth) {
                logger()->warning('If you get an error on the public endpoint, please authenticate.');
            }

            $base = AtProto::PublicEndpoint->value;
        } else {
            $base .= '/xrpc/';
        }

        return $base;
    }
}
