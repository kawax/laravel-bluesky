<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Session;

class OAuthSession extends AbstractSession
{
    public function did(string $default = ''): string
    {
        $did = $this->session->only(['did', 'sub', 'id'])->first();

        if (! empty($did)) {
            return (string) $did;
        }

        return $this->get('didDoc.id', $this->get('profile.did', $default));
    }

    public function handle(string $default = ''): string
    {
        return $this->get('profile.handle', $this->get('handle', $default));
    }

    public function issuer(string $default = ''): string
    {
        $iss = $this->session->only(['iss', 'issuer'])->first();

        if (! empty($iss)) {
            return (string) $iss;
        }

        return $this->get('pds.authorization_servers.{first}', $default);
    }

    public function displayName(string $default = ''): string
    {
        return $this->get('profile.displayName', $this->get('displayName', $default));
    }

    public function avatar(string $default = ''): string
    {
        return $this->get('profile.avatar', $this->get('avatar', $default));
    }

    public function token(string $default = ''): string
    {
        return $this->get('access_token', $default);
    }

    public function refresh(string $default = ''): string
    {
        return $this->get('refresh_token', $default);
    }
}
