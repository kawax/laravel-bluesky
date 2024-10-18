<?php

namespace Revolution\Bluesky\Agent;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\CredentialSession;

/**
 * App password based agent (deprecated).
 */
class LegacyAgent implements Agent
{
    public function __construct(
        protected CredentialSession $session,
    ) {
    }

    public static function create(CredentialSession $session): self
    {
        return new self($session);
    }

    public function http(bool $auth = true): PendingRequest
    {
        return Http::baseUrl(Bluesky::baseUrl())
            ->when($auth, function (PendingRequest $http) {
                $http->withToken(token: $this->token());
            });
    }

    public function session(?string $key = null, $default = null): array|string|null
    {
        return empty($key) ? $this->session->toArray() : $this->session->get($key, $default);
    }

    public function did(): string
    {
        return $this->session->did();
    }

    public function token(): string
    {
        return $this->session->token();
    }

    public function refresh(): string
    {
        return $this->session->refresh();
    }
}
