<?php

declare(strict_types=1);

namespace Revolution\Bluesky;

use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\Bluesky\Contracts\Factory;
use Revolution\Bluesky\Enums\AtProto;
use Revolution\Bluesky\Notifications\BlueskyMessage;

class BlueskyClient implements Factory
{
    use Macroable;
    use Conditionable;

    protected ?Collection $session = null;

    public function __construct(
        #[Config('bluesky.service', 'https://bsky.social')]
        protected string $service = 'https://bsky.social',
    ) {
        //
    }

    public function service(string $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function session(string $key = null): mixed
    {
        return empty($key) ? $this->session : $this->session?->get($key);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function login(string $identifier, #[\SensitiveParameter] string $password): static
    {
        $response = Http::baseUrl($this->baseUrl())
            ->post(AtProto::createSession->value, [
                'identifier' => $identifier,
                'password' => $password,
            ])->throw();

        $this->session = $response->collect();

        return $this;
    }

    /**
     * My feed.
     * @throws ConnectionException
     */
    public function feed(int $limit = 50, string $cursor = '', string $filter = 'posts_with_replies'): Response
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken($this->session('accessJwt'))
            ->get(AtProto::getAuthorFeed->value, [
                'actor' => $this->session('did'),
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
        return Http::baseUrl($this->baseUrl())
            ->withToken($this->session('accessJwt'))
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

        return Http::baseUrl($this->baseUrl())
            ->withToken($this->session('accessJwt'))
            ->post(AtProto::createRecord->value, [
                'repo' => $this->session('did'),
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
        return Http::baseUrl($this->baseUrl())
            ->withToken($this->session('accessJwt'))
            ->withBody($data, $type)
            ->post(AtProto::uploadBlob->value);
    }

    public function check(): bool
    {
        return ! empty($this->session['accessJwt']);
    }

    public function logout(): static
    {
        $this->session = null;

        return $this;
    }

    private function baseUrl(): string
    {
        return $this->service.'/xrpc/';
    }
}
