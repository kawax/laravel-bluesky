<?php

namespace Revolution\Bluesky\Agent;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Enums\AtProto;
use Revolution\Bluesky\Session\LegacySession;

/**
 * App password based agent (deprecated).
 */
class LegacyAgent implements Agent
{
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
    public function refreshToken(): static
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

    public function baseUrl(bool $auth = true): string
    {
        $base = $this->session->get('didDoc.service.0.serviceEndpoint');

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
