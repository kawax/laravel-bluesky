<?php

namespace Revolution\Bluesky\Session;

class CredentialSession extends AbstractSession
{
    public function token(): string
    {
        return $this->get('accessJwt', '');
    }

    public function refresh(): string
    {
        return $this->get('refreshJwt', '');
    }
}
