<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Agent;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Server;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Session\LegacySession;

/**
 * App password based agent.
 */
final class LegacyAgent implements Agent
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
    public function refreshSession(): self
    {
        $response = Http::baseUrl($this->baseUrl())
            ->withToken(token: $this->session->refresh())
            ->post(Server::refreshSession, null);

        $this->session = LegacySession::create($response->collect());

        return $this;
    }

    /**
     * @return ($key is non-empty-string ? mixed : LegacySession)
     */
    public function session(?string $key = null, mixed $default = null): mixed
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

    public function pdsUrl(string $default = ''): string
    {
        return $this->session->didDoc()->pdsUrl($default);
    }

    public function baseUrl(bool $auth = true): string
    {
        $base = $this->pdsUrl();

        if (! empty($base)) {
            return $base.'/xrpc/';
        }

        if ($auth) {
            $base = config('bluesky.service');
        } else {
            $base = config('bluesky.public_endpoint');
        }

        /** @var string $base */
        return $base.'/xrpc/';
    }
}
