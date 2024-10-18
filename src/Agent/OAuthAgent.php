<?php

namespace Revolution\Bluesky\Agent;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;

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
            ->when($auth, function (PendingRequest $http) {
                $dpop_proof = '';

                $http->withToken(token: $this->token(), type: 'DPoP')
                    ->withHeader('DPoP', $dpop_proof);
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
