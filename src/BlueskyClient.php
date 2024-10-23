<?php

declare(strict_types=1);

namespace Revolution\Bluesky;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Container\Container;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
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
use Revolution\Bluesky\Support\PDS;

class BlueskyClient implements Factory
{
    use Macroable;
    use Conditionable;

    protected ?Agent $agent = null;

    /**
     * OAuth authentication.
     *
     * @throws AuthenticationException
     */
    public function withToken(#[\SensitiveParameter] ?OAuthSession $token): self
    {
        if (empty($token?->refresh())) {
            throw new AuthenticationException();
        }

        $this->agent = OAuthAgent::create($token);

        return $this;
    }

    /**
     * App password authentication.
     */
    public function login(string $identifier, #[\SensitiveParameter] string $password): self
    {
        $response = Http::baseUrl($this->entryway().'/xrpc/')
            ->post(AtProto::createSession->value, [
                'identifier' => $identifier,
                'password' => $password,
            ]);

        $session = LegacySession::create($response->collect());
        $this->agent = LegacyAgent::create($session);

        return $this;
    }

    public function agent(): ?Agent
    {
        return $this->agent;
    }

    public function withAgent(?Agent $agent): self
    {
        $this->agent = $agent;

        return $this;
    }

    public function http(bool $auth = true): PendingRequest
    {
        if (! $this->check()) {
            return Http::baseUrl(AtProto::PublicEndpoint->value);
        }

        return $this->agent()->http($auth);
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
     *
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
     * @throws ConnectionException
     */
    public function createRecord(string $repo, string $collection, array $record): Response
    {
        return $this->http()
            ->post(AtProto::createRecord->value, [
                'repo' => $repo,
                'collection' => $collection,
                'record' => $record,
            ]);
    }

    /**
     * Create new post.
     *
     * @throws ConnectionException
     */
    public function post(string|BlueskyMessage $text): Response
    {
        $message = $text instanceof BlueskyMessage ? $text : BlueskyMessage::create($text);

        $record = collect($message->toArray())
            ->put('createdAt', now()->toISOString())
            ->reject(fn ($item) => blank($item))
            ->toArray();

        return $this->createRecord(
            repo: $this->agent()->did(),
            collection: 'app.bsky.feed.post',
            record: $record,
        );
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

    public function refreshSession(): self
    {
        $this->agent()?->refreshSession();

        return $this;
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

    public function identity(): Identity
    {
        return Container::getInstance()->make(Identity::class);
    }

    public function pds(): PDS
    {
        return Container::getInstance()->make(PDS::class);
    }

    public function check(): bool
    {
        return ! empty($this->agent()?->token());
    }

    public function logout(): self
    {
        $this->agent = null;

        return $this;
    }

    public function entryway(): string
    {
        return 'https://'.AtProto::Entryway->value;
    }
}
