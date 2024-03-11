<?php

declare(strict_types=1);

namespace Revolution\Bluesky;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Macroable;
use Revolution\Bluesky\Contracts\Factory;
use Revolution\Bluesky\Enums\AtProto;

class BlueskyClient implements Factory
{
    use Macroable;

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
    public function login(string $identifier, string $password): static
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
    public function feed(string $filter = 'posts_with_replies'): Response
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken($this->session('accessJwt'))
            ->get(AtProto::getAuthorFeed->value, [
                'actor' => $this->session('did'),
                'filter' => $filter,
            ]);
    }

    /**
     * My timeline.
    */
    public function timeline(string $cursor = ''): Response
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken($this->session('accessJwt'))
            ->get(AtProto::getTimeline->value, [
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

    private function baseUrl(): string
    {
        return $this->service.'/xrpc/';
    }
}
