<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socialite\Concerns;

use Illuminate\Support\Facades\Http;

trait WithAuthServer
{
    protected ?array $auth_server_meta = [];

    /**
     * @link  https://bsky.social/.well-known/oauth-authorization-server
     *
     * @return ($key is non-empty-string ? string : array)
     */
    protected function authServerMeta(?string $key = null, string $default = ''): array|string
    {
        if (empty($this->auth_server_meta)) {
            $this->auth_server_meta = Http::baseUrl($this->authUrl())
                ->get('/.well-known/oauth-authorization-server')
                ->json();
        }

        if (empty($key)) {
            return $this->auth_server_meta;
        }

        return data_get($this->auth_server_meta, $key, $default);
    }
}
