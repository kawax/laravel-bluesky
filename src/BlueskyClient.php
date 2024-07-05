<?php

declare(strict_types=1);

namespace Revolution\Bluesky;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\Bluesky\Contracts\Factory;
use Revolution\Bluesky\Enums\AtProto;

class BlueskyClient implements Factory
{
    use Macroable;
    use Conditionable;

    protected ?Collection $session = null;

    public function __construct(protected string $service = 'https://bsky.social')
    {
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
     */
    public function post(string $text): Response
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken($this->session('accessJwt'))
            ->post(AtProto::createRecord->value, [
                'repo' => $this->session('did'),
                'collection' => 'app.bsky.feed.post',
                'record' => [
                    'text' => $text,
                    'createdAt' => now()->toISOString(),
                ]
            ]);
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
