<?php

namespace Revolution\Bluesky\Session;

class OAuthSession extends AbstractSession
{
    public function did(): string
    {
        return $this->session->only(['did', 'sub', 'id'])->first(default: '');
    }

    public function token(): string
    {
        return $this->get('access_token', '');
    }

    public function refresh(): string
    {
        return $this->get('refresh_token', '');
    }
}
