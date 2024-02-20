<?php

declare(strict_types=1);

namespace Revolution\Bluesky;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Macroable;
use Revolution\Bluesky\Contracts\Factory;

class BlueskyClient implements Factory
{
    use Macroable;

    protected Collection $session;

    public function __construct(protected string $service = 'https://bsky.social')
    {
        //
    }

    public function service(string $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function session(string $key): mixed
    {
        return $this->session->get($key);
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
     *
     * @throws RequestException
     */
    public function feed(string $filter = 'posts_with_replies'): Collection
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken($this->session->get('accessJwt'))
            ->get(AtProto::getAuthorFeed->value, [
                'actor' => $this->session->get('did'),
                'filter' => $filter,
            ])
            ->throw()
            ->collect();
    }

    /**
     * My timeline.
     *
     * @throws RequestException
     */
    public function timeline(string $cursor = ''): Collection
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken($this->session->get('accessJwt'))
            ->get(AtProto::getTimeline->value, [
                'cursor' => $cursor,
            ])
            ->throw()
            ->collect();
    }

    /**
     * @throws RequestException
     */
    public function post(string $text): Collection
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken($this->session->get('accessJwt'))
            ->post(AtProto::createRecord->value, [
                'repo' => $this->session->get('did'),
                'collection' => 'app.bsky.feed.post',
                'record' => [
                    'text' => $text,
                    'createdAt' => now()->toISOString(),
                ]
            ])
            ->throw()
            ->collect();
    }

    private function baseUrl(): string
    {
        return $this->service.'/xrpc/';
    }
}
