<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socalite\Concerns;

use Illuminate\Support\Facades\Http;

trait WithAuthServer
{
    protected ?array $auth_server_meta = [];

    protected function authServerMeta(?string $key = null, ?string $default = null): array|string|null
    {
        $auth_url = $this->authUrl();

        if (empty($this->auth_server_meta)) {
            $this->auth_server_meta = Http::baseUrl($auth_url)
                ->get('/.well-known/oauth-authorization-server')
                ->json();
        }

        if (empty($key)) {
            return $this->auth_server_meta;
        }

        return data_get($this->auth_server_meta, $key, $default);
    }
}
