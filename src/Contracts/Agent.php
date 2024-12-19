<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Contracts;

use Illuminate\Http\Client\PendingRequest;
use Revolution\Bluesky\Session\AbstractSession;

interface Agent
{
    public function http(bool $auth = true): PendingRequest;

    public function refreshSession(): self;

    /**
     * @return ($key is non-empty-string ? mixed : AbstractSession)
     */
    public function session(?string $key = null, mixed $default = null): mixed;

    public function did(string $default = ''): string;

    public function token(string $default = ''): string;

    public function refresh(string $default = ''): string;

    public function baseUrl(bool $auth = true): string;
}
