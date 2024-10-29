<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Session;

class LegacySession extends AbstractSession
{
    public function token(string $default = ''): string
    {
        return $this->get('accessJwt', $default);
    }

    public function refresh(string $default = ''): string
    {
        return $this->get('refreshJwt', $default);
    }

    public function email(string $default = ''): string
    {
        return $this->get('email', $default);
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
