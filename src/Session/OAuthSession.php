<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Session;

use Illuminate\Support\Carbon;
use Revolution\Bluesky\Support\Identity;

class OAuthSession extends AbstractSession
{
    public function did(string $default = ''): string
    {
        return (string) $this->session
            ->dot()
            ->only(['did', 'sub', 'id', 'didDoc.id', 'profile.did'])
            ->first(fn ($did) => Identity::isDID($did), $default);
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

    public function tokenExpired(): bool
    {
        $token_created_at = $this->get('token_created_at');
        $expires_in = $this->get('expires_in');

        if (empty($token_created_at) || empty($expires_in)) {
            return true;
        }

        $date = Carbon::parse($token_created_at, 'UTC')
            ->addSeconds($expires_in);

        return $date->lessThan(now());
    }
}
