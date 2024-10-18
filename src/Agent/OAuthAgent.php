<?php

namespace Revolution\Bluesky\Agent;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
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
        protected OAuthSession $session,
    ) {
    }

    public static function create(OAuthSession $session): self
    {
        return new self($session);
    }

    public function http(bool $auth = true): PendingRequest
    {
        $base = $this->serviceEndpoint();
        if (empty($base)) {
            $base = Bluesky::baseUrl();
        } else {
            $base .= '/xrpc/';
        }

        dump($base);

        return Http::baseUrl($base)
            ->when($auth, $this->dpop(...));
    }

    protected function dpop(PendingRequest $http): PendingRequest
    {
        $http->withToken($this->token(), 'DPoP')
            ->withRequestMiddleware(function (RequestInterface $request) {
                $payload = [
                    'nonce' => $this->session('dpop_nonce', ''),
                    'iss' => $this->session('iss'),
                    'htu' => $request->getUri(),
                    'htm' => $request->getMethod(),
                    'jti' => Str::random(40),
                    'iat' => now()->timestamp,
                    'exp' => now()->addSeconds(30)->timestamp,
                    'ath' => DPoP::createCodeChallenge($this->token()),
                ];

                $dpop_private_jwk = DPoP::load($this->session('dpop_private_key'));
                $dpop_proof = DPoP::proof($payload, $dpop_private_jwk);

                return $request->withHeader('DPoP', $dpop_proof);
            });

        return $http->retry(times: 2, sleepMilliseconds: 10, when: function (Exception $exception, PendingRequest $request) {
            if (! $exception instanceof RequestException || $exception->response->status() !== 401) {
                return false;
            }

            $dpop_nonce = $exception->response->header('DPoP-Nonce');

            $this->session->put('dpop_nonce', $dpop_nonce);

            return true;
        }, throw: false);
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

    public function serviceEndpoint(): ?string
    {
        return Arr::get($this->session->toArray(), 'service.0.serviceEndpoint');
    }
}
