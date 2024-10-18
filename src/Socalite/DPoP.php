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

    public static function proof(string $par_url, string $nonce, JsonWebKey $jwk): string
    {
        if (empty($nonce)) {
            return '';
        }

        $pub_jwk = $jwk->asPublic();

        $head = [
            'typ' => 'dpop+jwt',
            'alg' => JsonWebKey::ALG,
            'jwk' => $pub_jwk->toArray(),
        ];

        $payload = [
            'nonce' => $nonce,
            'htm' => 'POST',
            'htu' => $par_url,
            'jti' => Str::random(40),
            'iat' => now()->timestamp,
            'exp' => now()->addSeconds(30)->timestamp,
        ];

        return JsonWebToken::encode($head, $payload, $jwk->key());
    }
}
