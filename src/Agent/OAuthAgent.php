<?php

namespace Revolution\Bluesky\Agent;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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
        return Http::baseUrl(Bluesky::baseUrl())
            ->when($auth, $this->dpop(...));
    }

    protected function dpop(PendingRequest $http): PendingRequest
    {
        $http->withToken(token: $this->token(), type: 'DPoP')
            ->beforeSending(function (Request $request, PendingRequest $http) {
                $url = $request->url();
                info($url);
                session()->put('request_url', $request->url());
            });

        return $http->retry(times: 2, sleepMilliseconds: 10, when: function ($exception, PendingRequest $request) {
            if (! $exception instanceof RequestException || $exception->response->status() !== 401) {
                return false;
            }

            $dpop_nonce = $exception->response->header('DPoP-Nonce');
            info('retry.nonce', $dpop_nonce);

            $url = session()->get('request_url');
            info('retry.url', $url);

            $payload = [
                'iss' => $this->session('iss'),
                'htu' => $url,
                'htm' => 'POST',
                'jti' => Str::random(40),
                'iat' => now()->timestamp,
                'exp' => now()->addSeconds(30)->timestamp,
                'ath' => DPoP::createCodeChallenge($this->token()),
            ];

            $dpop_private_jwk = DPoP::load($this->session('dpop_private_key'));
            $dpop_proof = DPoP::proof($payload, $dpop_private_jwk);
            info('retry.proof', $dpop_proof);

            $request->withHeader('DPoP', $dpop_proof);

            return true;
        });
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
}
