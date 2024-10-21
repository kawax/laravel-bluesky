<?php

namespace Revolution\Bluesky\Socalite;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;

class DPoP
{
    public const AUTH_NONCE = 'dpop_auth_nonce';

    public const API_NONCE = 'dpop_api_nonce';

    protected const TYP = 'dpop+jwt';

    /**
     * @param  string|null  $key  url-safe base64 encoded private key
     */
    public static function load(?string $key = null): JsonWebKey
    {
        return BlueskyKey::load($key)->toJWK();
    }

    /**
     * Proof for OAuth.
     */
    public static function authProof(
        JsonWebKey $jwk,
        string $url,
        ?string $nonce = '',
        string $method = 'POST',
    ): string {
        $head = [
            'typ' => self::TYP,
            'alg' => JsonWebKey::ALG,
            'jwk' => $jwk->asPublic()->toArray(),
        ];

        $payload = [
            'nonce' => $nonce,
            'htm' => $method,
            'htu' => $url,
            'jti' => Str::random(64),
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(60)->timestamp,
        ];

        return JsonWebToken::encode($head, $payload, $jwk->key());
    }

    /**
     * Proof for API request.
     */
    public static function apiProof(
        JsonWebKey $jwk,
        string $iss,
        string $url,
        string $token,
        ?string $nonce = '',
        string $method = 'POST',
    ): string {
        $head = [
            'typ' => self::TYP,
            'alg' => JsonWebKey::ALG,
            'jwk' => $jwk->asPublic()->toArray(),
        ];

        $payload = [
            'nonce' => $nonce,
            'iss' => $iss,
            'htu' => $url,
            'htm' => $method,
            'jti' => Str::random(64),
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(3600)->timestamp,
            'ath' => self::createCodeChallenge($token),
        ];

        return JsonWebToken::encode($head, $payload, $jwk->key());
    }

    protected static function createCodeChallenge(string $code): string
    {
        $hashed = hash('sha256', $code, true);

        return JWT::urlsafeB64Encode($hashed);
    }
}
