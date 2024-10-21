<?php

namespace Revolution\Bluesky\Socalite\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

trait WithAuthServer
{
    protected ?array $auth_server_meta = [];

    protected function authServerMeta(string $key = '', ?string $default = null): array|string|null
    {
        $auth_url = $this->authUrl();

        if (empty($this->auth_server_meta)) {
            $this->auth_server_meta = Http::get($auth_url.'/.well-known/oauth-authorization-server')
                ->json();
        }

        if (empty($key)) {
            return $this->auth_server_meta;
        }

        return Arr::get($this->auth_server_meta, $key, $default);
    }
}
