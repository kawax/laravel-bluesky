<?php

namespace Revolution\Bluesky\Session;

class OAuthSession extends AbstractSession
{
    public function did(): string
    {
        return $this->get('did', $this->get('sub', ''));
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
