<?php

namespace Revolution\Bluesky\Socalite\Concerns;

use Illuminate\Support\Str;
use Revolution\Bluesky\Socalite\BlueskyKey;
use Revolution\Bluesky\Socalite\JsonWebKey;
use Revolution\Bluesky\Socalite\JsonWebToken;

trait WithClientAssertion
{
    protected function getClientAssertion(string $auth_url): string
    {
        $client_secret_jwk = BlueskyKey::load()->toJWK();

        $head = [
            'alg' => JsonWebKey::ALG,
            'kid' => $client_secret_jwk->kid(),
        ];

        $payload = [
            'iss' => $this->clientId,
            'sub' => $this->clientId,
            'aud' => $auth_url,
            'jti' => Str::random(40),
            'iat' => now()->timestamp,
        ];

        return JsonWebToken::encode($head, $payload, $client_secret_jwk->key());
    }
}
