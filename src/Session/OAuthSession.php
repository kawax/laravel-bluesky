<?php

namespace Revolution\Bluesky\Session;

class OAuthSession extends AbstractSession
{
    public function did(): ?string
    {
        $did = $this->session->only(['did', 'sub', 'id'])->first();

        if (! empty($did)) {
            return $did;
        }

        return $this->get('didDoc.id', $this->get('profile.did'));
    }

    public function handle(): ?string
    {
        return $this->get('profile.handle', $this->get('handle'));
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
