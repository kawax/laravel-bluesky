<?php

namespace Revolution\Bluesky\Contracts;

use Illuminate\Http\Client\PendingRequest;

interface Agent
{
    public function http(bool $auth = true): PendingRequest;

    public function refreshToken(): static;

    public function session(?string $key = null, $default = null): array|string|null;

    public function did(): string;

    public function handle(): string;

    public function token(): string;

    public function refresh(): string;

    public function baseUrl(bool $auth = true): string;
}
