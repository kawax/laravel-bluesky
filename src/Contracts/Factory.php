<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Contracts;

use BackedEnum;
use Illuminate\Http\Client\Response;
use Revolution\Bluesky\Client\AtpClient;
use Revolution\Bluesky\Session\OAuthSession;

interface Factory
{
    public function withToken(OAuthSession $token): self;

    public function login(string $identifier, string $password): self;

    public function client(bool $auth = true): XrpcClient|AtpClient;

    public function send(BackedEnum|string $api, string $method = 'get', bool $auth = true, ?array $params = null, ?callable $callback = null): Response;

    public function agent(): ?Agent;

    public function withAgent(?Agent $agent): self;
}
