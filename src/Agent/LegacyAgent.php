<?php

namespace Revolution\Bluesky\Agent;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Enums\AtProto;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\LegacySession;

/**
 * App password based agent.
 */
class LegacyAgent implements Agent
{
    use Macroable;
    use Conditionable;

    public function __construct(
        protected LegacySession $session,
    ) {
    }

    public static function create(LegacySession $session): self
    {
        return new self($session);
    }

    public function http(bool $auth = true): PendingRequest
    {
        return Http::baseUrl($this->baseUrl($auth))
            ->when($auth, function (PendingRequest $http) {
                $http->withToken(token: $this->token());
            });
    }

    /**
     * @throws ConnectionException
     */
    public function refreshToken(): self
    {
        $response = Http::baseUrl($this->baseUrl())
            ->withToken(token: $this->session->refresh())
            ->post(AtProto::refreshSession->value);

        $this->session = LegacySession::create($response->collect());

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
        $didDoc = $this->session('didDoc');

        return Bluesky::pds()->endpoint($didDoc, $default);
    }

    public function baseUrl(bool $auth = true): string
    {
        $base = $this->pdsUrl();

        if (! empty($base)) {
            return $base.'/xrpc/';
        }

        if ($auth) {
            return 'https://'.AtProto::Entryway->value.'/xrpc/';
        } else {
            return AtProto::PublicEndpoint->value;
        }
    }
}
