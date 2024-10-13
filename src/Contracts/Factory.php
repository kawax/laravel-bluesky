<?php

namespace Revolution\Bluesky\Contracts;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Revolution\Bluesky\Notifications\BlueskyMessage;

interface Factory
{
    public function service(string $service): static;

    public function session(string $key = null): mixed;

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function login(string $identifier, string $password): static;

    /**
     * @throws ConnectionException
     */
    public function resolveHandle(string $handle): Response;

    /**
     * My feed.
     * @throws ConnectionException
     */
    public function feed(int $limit = 50, string $cursor = '', string $filter = 'posts_with_replies'): Response;

    /**
     * My timeline.
     * @throws ConnectionException
     */
    public function timeline(int $limit = 50, string $cursor = ''): Response;

    /**
     * Create new post.
     * @throws ConnectionException
     */
    public function post(string|BlueskyMessage $text): Response;

    /**
     * Upload blob.
     *
     * @throws ConnectionException
     */
    public function uploadBlob(mixed $data, string $type = 'image/png'): Response;

    public function check(): bool;

    public function logout(): static;
}
