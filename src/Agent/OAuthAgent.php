<?php

namespace Revolution\Bluesky\Agent;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\Token;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Enums\AtProto;
use Revolution\Bluesky\Events\OAuthSessionUpdated;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Socalite\Key\DPoP;
use Revolution\Bluesky\Support\DidDocument;
use Revolution\Bluesky\Support\Identity;

/**
 * OAuth based agent.
 */
class OAuthAgent implements Agent
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

    public function http(bool $auth = true): PendingRequest
    {
        if ($auth && $this->tokenExpired()) {
            $this->refreshToken();
        }

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
            throw new InvalidArgumentException('Missing refresh token.');
        }

        /** @var Token $token */
        $token = Socialite::driver('bluesky')
            ->issuer($this->session->issuer(default: AtProto::Entryway->value))
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
        if (empty($did) || ! Identity::isDID($did)) {
            return $this;
        }

        $didDoc = Bluesky::identity()->resolveDID($did)->json();
        //$this->session->merge($didDoc);
        $this->session->put('didDoc', $didDoc);

        $profile = Bluesky::withAgent($this)->profile($did)->json();
        //$this->session->merge($profile);
        $this->session->put('profile', $profile);

        $pds_url = $this->pdsUrl();
        if (! $this->session->has('iss') && ! empty($pds_url)) {
            $pds = Bluesky::pds()->resource($pds_url);

            $this->session->put('pds', $pds);
            $this->session->put('iss', data_get($pds, 'authorization_servers.{first}'));
        }

        return $this;
    }

    public function session(?string $key = null, $default = null): mixed
    {
        return empty($key) ? $this->session->toArray() : $this->session->get($key, $default);
    }

    public function did(): ?string
    {
        return $this->session->did();
    }

    public function handle(): ?string
    {
        return $this->session->handle();
    }

    public function token(): ?string
    {
        return $this->session->token();
    }

    public function tokenExpired(): bool
    {
        $token_created_at = $this->session->get('token_created_at');
        $expires_in = $this->session->get('expires_in');

        if (empty($token_created_at) || empty($expires_in)) {
            return true;
        }

        $date = Carbon::parse($token_created_at, 'UTC')
            ->addSeconds($expires_in);

        return $date->lessThan(now());
    }

    public function pdsUrl(?string $default = null): ?string
    {
        return DidDocument::create($this->session('didDoc'))->endpoint($default);
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
