<?php
declare(strict_types=1);

namespace Revolution\Bluesky\Socialite\Key;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;

final class DPoP
{
    public const AUTH_NONCE = 'dpop_auth_nonce';

    public const API_NONCE = 'dpop_api_nonce';

    public const SESSION_KEY = 'bluesky_dpop_key';

    protected const TYP = 'dpop+jwt';

    /**
     * Generate new private key for DPoP.
     *
     * @return string url-safe base64 encoded private key
     */
    public static function generate(): string
    {
        return OAuthKey::create()->privateB64();
    }

    public static function load(): JsonWebKey
    {
        return OAuthKey::load(session()->remember(self::SESSION_KEY, fn () => self::generate()))->toJWK();
    }

    /**
     * Proof for OAuth and token request.
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
            'exp' => now()->addSeconds(600)->timestamp,
        ];

        return JsonWebToken::encode($head, $payload, $jwk->toPEM());
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
            'exp' => now()->addSeconds(600)->timestamp,
            'ath' => self::createCodeChallenge($token),
        ];

        return JsonWebToken::encode($head, $payload, $jwk->toPEM());
    }

    protected static function createCodeChallenge(string $code): string
    {
        $hashed = hash('sha256', $code, true);

        return JWT::urlsafeB64Encode($hashed);
    }
}
