<?php

namespace Revolution\Bluesky\Contracts;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Revolution\Bluesky\Notifications\BlueskyMessage;
use Revolution\Bluesky\Session\LegacySession;
use Revolution\Bluesky\Session\OAuthSession;

interface Factory
{
    public function withToken(OAuthSession $token): self;

    public function login(string $identifier, string $password): self;

    public function agent(): ?Agent;

    public function withAgent(?Agent $agent): self;

    public function http(bool $auth = true): PendingRequest;

    public function resolveHandle(string $handle): Response;

    public function feed(?string $actor = null, int $limit = 50, string $cursor = '', string $filter = 'posts_with_replies'): Response;

    public function timeline(int $limit = 50, string $cursor = ''): Response;

    public function post(string|BlueskyMessage $text): Response;

    public function uploadBlob(mixed $data, string $type = 'image/png'): Response;
}
