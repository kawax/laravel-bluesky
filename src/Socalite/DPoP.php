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

    public static function authProof(array $payload, JsonWebKey $jwk): string
    {
        $pub_jwk = $jwk->asPublic();

        $head = [
            'typ' => 'dpop+jwt',
            'alg' => JsonWebKey::ALG,
            'jwk' => $pub_jwk->toArray(),
        ];

        return JsonWebToken::encode($head, $payload, $jwk->key());
    }

    public static function apiProof(
        string $nonce,
        string $method,
        string $url,
        string $iss,
        string $code,
        JsonWebKey $jwk): string
    {
        $head = [
            'typ' => 'dpop+jwt',
            'alg' => JsonWebKey::ALG,
            'jwk' => $jwk->asPublic()->toArray(),
        ];

        $payload = [
            'nonce' => $nonce,
            'iss' => $iss,
            'htu' => $url,
            'htm' => $method,
            'jti' => Str::random(40),
            'iat' => now()->timestamp,
            'exp' => now()->addSeconds(30)->timestamp,
            'ath' => self::createCodeChallenge($code),
        ];

        return JsonWebToken::encode($head, $payload, $jwk->key());
    }

    public static function createCodeChallenge(string $code): string
    {
        $hashed = hash('sha256', $code, true);

        return rtrim(strtr(base64_encode($hashed), '+/', '-_'), '=');
    }
}
