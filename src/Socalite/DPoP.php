<?php

namespace Revolution\Bluesky\Socalite;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;

class DPoP
{
    public static function generate(): string
    {
        return JWT::urlsafeB64Encode(BlueskyKey::create()->privatePEM());
    }

    public static function load(string $key): JsonWebKey
    {
        return BlueskyKey::load($key)->toJWK();
    }

    public static function proof(array $payload, JsonWebKey $jwk): string
    {
        $pub_jwk = $jwk->asPublic();

        $head = [
            'typ' => 'dpop+jwt',
            'alg' => JsonWebKey::ALG,
            'jwk' => $pub_jwk->toArray(),
        ];

        return JsonWebToken::encode($head, $payload, $jwk->key());
    }

    public static function createCodeChallenge(string $code): string
    {
        $hashed = hash('sha256', $code, true);

        return rtrim(strtr(base64_encode($hashed), '+/', '-_'), '=');
    }
}
