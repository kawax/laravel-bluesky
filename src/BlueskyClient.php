<?php

declare(strict_types=1);

namespace Revolution\Bluesky;

use Illuminate\Container\Container;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use Revolution\Bluesky\Agent\LegacyAgent;
use Revolution\Bluesky\Agent\OAuthAgent;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Contracts\Factory;
use Revolution\Bluesky\Enums\AtProto;
use Revolution\Bluesky\Notifications\BlueskyMessage;
use Revolution\Bluesky\Session\LegacySession;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Support\Identity;

class BlueskyClient implements Factory
{
    use Macroable;
    use Conditionable;

    protected ?Agent $agent = null;

    /**
     * OAuth based authentication.
     */
    public function withToken(#[\SensitiveParameter] OAuthSession $token): static
    {
        $this->agent = OAuthAgent::create($token);

        return $this;
    }

    /**
     * Username / password based authentication.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function login(string $identifier, #[\SensitiveParameter] string $password): static
    {
        $response = Http::baseUrl('https://'.AtProto::Entryway->value.'/xrpc/')
            ->post(AtProto::createSession->value, [
                'identifier' => $identifier,
                'password' => $password,
            ])->throw();

        $session = LegacySession::create($response->collect());
        $this->agent = LegacyAgent::create($session);

        return $this;
    }

    public function agent(): ?Agent
    {
        return $this->agent;
    }

    public function withAgent(?Agent $agent): static
    {
        $this->agent = $agent;

        return $this;
    }

    public function http(bool $auth = true): PendingRequest
    {
        if (! $this->check()) {
            return Http::baseUrl($this->baseUrl());
        }

        return $this->agent()->http($auth);
    }

    /**
     * @param  string  $handle  e.g. "alice.test"
     *
     * @throws ConnectionException
     */
    public function resolveHandle(string $handle): Response
    {
        if (! Identity::isHandle($handle)) {
            throw new InvalidArgumentException("The handle '$handle' is not a valid handle.");
        }

        return $this->http()
            ->get(AtProto::resolveHandle->value, [
                'handle' => $handle,
            ]);
    }

    /**
     * @param  string|null  $actor  DID or handle.
     *
     * @throws ConnectionException
     */
    public function profile(?string $actor = null): Response
    {
        return $this->http()
            ->get(AtProto::getProfile->value, [
                'actor' => $actor ?? $this->agent()?->did() ?? '',
            ]);
    }

    /**
     * getAuthorFeed.
     *
     * @param  string|null  $actor  DID or handle.
     *
     * @throws ConnectionException
     */
    public function feed(?string $actor = null, int $limit = 50, string $cursor = '', string $filter = 'posts_with_replies'): Response
    {
        return $this->http()
            ->get(AtProto::getAuthorFeed->value, [
                'actor' => $actor ?? $this->agent()?->did() ?? '',
                'limit' => $limit,
                'cursor' => $cursor,
                'filter' => $filter,
            ]);
    }

    /**
     * My timeline.
     * @throws ConnectionException
     */
    public function timeline(int $limit = 50, string $cursor = ''): Response
    {
        return $this->http()
            ->get(AtProto::getTimeline->value, [
                'limit' => $limit,
                'cursor' => $cursor,
            ]);
    }

    /**
     * Create new post.
     * @throws ConnectionException
     */
    public function post(string|BlueskyMessage $text): Response
    {
        $message = $text instanceof BlueskyMessage ? $text : BlueskyMessage::create($text);

        $record = collect($message->toArray())
            ->put('createdAt', now()->toISOString())
            ->reject(fn ($item) => blank($item))
            ->toArray();

        return $this->http()
            ->post(AtProto::createRecord->value, [
                'repo' => $this->agent()->did(),
                'collection' => 'app.bsky.feed.post',
                'record' => $record,
            ]);
    }

    /**
     * Upload blob.
     *
     * @throws ConnectionException
     */
    public function uploadBlob(mixed $data, string $type = 'image/png'): Response
    {
        return $this->http()
            ->withBody($data, $type)
            ->post(AtProto::uploadBlob->value);
    }

    /**
     * @throws ConnectionException
     */
    public function refreshCredentialSession(): static
    {
        $response = Http::baseUrl($this->baseUrl())
            ->withToken(token: $this->agent()->refresh())
            ->post(AtProto::refreshSession->value);

        $session = LegacySession::create($response->collect());
        $this->withAgent(LegacyAgent::create($session));

        return $this;
    }

    public function identity(): Identity
    {
        return Container::getInstance()->make(Identity::class);
    }

    public function check(): bool
    {
        return ! empty($this->agent);
    }

    public function logout(): static
    {
        $this->agent = null;

        return $this;
    }

    public static function baseUrl(): string
    {
        return AtProto::PublicEndpoint->value.'/xrpc/';
    }
}
