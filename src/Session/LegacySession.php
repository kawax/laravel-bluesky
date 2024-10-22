<?php

namespace Revolution\Bluesky\Session;

class LegacySession extends AbstractSession
{
    public function token(): ?string
    {
        return $this->get('accessJwt');
    }

    public function refresh(): ?string
    {
        return $this->get('refreshJwt');
    }

    public function email(): ?string
    {
        return $this->get('email');
    }

    public function emailConfirmed(): bool
    {
        return (bool) $this->get('emailConfirmed');
    }

    public function active(): bool
    {
        return (bool) $this->get('active');
    }
}
