<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Session;

class OAuthSession extends AbstractSession
{
    public function did(): ?string
    {
        $did = $this->session->only(['did', 'sub', 'id'])->first();

        if (! empty($did)) {
            return (string) $did;
        }

        return $this->get('didDoc.id', $this->get('profile.did'));
    }

    public function handle(): ?string
    {
        return $this->get('profile.handle', $this->get('handle'));
    }

    public function issuer(?string $default = null): ?string
    {
        $iss = $this->session->only(['iss', 'issuer'])->first();

        if (! empty($iss)) {
            return (string) $iss;
        }

        return $this->get('pds.authorization_servers.{first}', $default);
    }

    public function displayName(): ?string
    {
        return $this->get('profile.displayName', $this->get('displayName'));
    }

    public function avatar(): ?string
    {
        return $this->get('profile.avatar', $this->get('avatar'));
    }

    public function token(): ?string
    {
        return $this->get('access_token');
    }

    public function refresh(): ?string
    {
        return $this->get('refresh_token');
    }
}
