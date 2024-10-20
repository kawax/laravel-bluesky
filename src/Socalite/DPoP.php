<?php

namespace Revolution\Bluesky\Socalite;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;

class DPoP
{
    public const AUTH_NONCE = 'bluesky.dpop_auth_nonce';

    public const API_NONCE = 'dpop_api_nonce';

    /**
     * Generate new private key for DPoP(url-safe base64 encoded).
     */
    public static function generate(): string
    {
        return JWT::urlsafeB64Encode(BlueskyKey::create()->privatePEM());
    }

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
        string $nonce = '',
        string $method = 'POST',
    ): string {
        $head = [
            'typ' => 'dpop+jwt',
            'alg' => JsonWebKey::ALG,
            'jwk' => $jwk->asPublic()->toArray(),
        ];

        $payload = [
            'nonce' => $nonce,
            'htm' => $method,
            'htu' => $url,
            'jti' => Str::random(64),
            'iat' => now()->timestamp,
            'exp' => now()->addSeconds(30)->timestamp,
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
        string $nonce = '',
        string $method = 'POST',
    ): string {
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
            'jti' => Str::random(64),
            'iat' => now()->timestamp,
            'exp' => now()->addSeconds(30)->timestamp,
            'ath' => self::createCodeChallenge($token),
        ];

        return JsonWebToken::encode($head, $payload, $jwk->key());
    }

    public static function createCodeChallenge(string $code): string
    {
        $hashed = hash('sha256', $code, true);

        return rtrim(strtr(base64_encode($hashed), '+/', '-_'), '=');
    }
}
