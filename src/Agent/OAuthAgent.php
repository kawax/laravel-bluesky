<?php

namespace Revolution\Bluesky\Agent;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Socalite\DPoP;

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
        return Http::baseUrl($this->baseUrl())
            ->when($auth, $this->dpop(...));
    }

    protected function dpop(PendingRequest $http): PendingRequest
    {
        return $http->withToken($this->token(), 'DPoP')
            ->withRequestMiddleware(function (RequestInterface $request) {
                $dpop_proof = DPoP::apiProof(
                    jwk: DPoP::load($this->session('dpop_private_key')),
                    iss: $this->session('iss'),
                    url: $request->getUri(),
                    token: $this->token(),
                    nonce: $this->session('dpop_nonce', ''),
                    method: $request->getMethod(),
                );

                return $request->withHeader('DPoP', $dpop_proof);
            })->withResponseMiddleware(function (ResponseInterface $response) {
                $dpop_nonce = collect($response->getHeader('DPoP-Nonce'))->first();

                $this->session->put('dpop_nonce', $dpop_nonce);

                return $response;
            })->retry(times: 2, throw: false);
    }

    public function session(?string $key = null, $default = null): array|string|null
    {
        return empty($key) ? $this->session->toArray() : $this->session->get($key, $default);
    }

    public function did(): string
    {
        return $this->session('did', '');
    }

    public function token(): string
    {
        return $this->session('access_token', '');
    }

    public function refresh(): string
    {
        return $this->session('refresh_token', '');
    }

    public function baseUrl(): string
    {
        $base = $this->session->get('service.0.serviceEndpoint');

        if (empty($base)) {
            $base = Bluesky::baseUrl();
        } else {
            $base .= '/xrpc/';
        }

        return $base;
    }
}
