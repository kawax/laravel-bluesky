<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socialite\Key;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Revolution\Bluesky\Crypto\P256;

final class JsonWebToken
{
    /**
     * @param  string  $key  Private key PEM
     */
    public static function encode(array $head, array $payload, string $key, string $alg = P256::ALG): string
    {
        return JWT::encode(
            payload: $payload,
            key: $key,
            alg: $alg,
            head: $head,
        );
    }

    /**
     * Get payload from JsonWebToken.
     *
     * ```
     * [$header, $payload, $sig] = JsonWebToken::explode('****.****.****');
     *
     * $header
     * [
     *     'type' => 'JWT',
     *     'alg' => 'ES256K',
     * ]
     *
     * $payload
     * [
     *     'iss' => 'did',
     *     'aud' => '',
     *     'exp' => int,
     * ]
     *
     * $sig
     * Remains url-safe base64 encoded
     * ```
     * ```
     * // Decode sig
     * use Firebase\JWT\JWT;
     *
     * $sig = JWT::urlsafeB64Decode($sig);
     * ```
     *
     * @return null|array<array{type: string, alg: string}, array{iss: string, aud: string, exp: int}, string>
     */
    public static function explode(?string $token): ?array
    {
        if (Str::substrCount($token, '.') !== 2) {
            return null;
        }

        [$header, $payload, $sig] = explode(separator: '.', string: $token, limit: 3);

        $header = json_decode(JWT::urlsafeB64Decode($header), true);
        $payload = json_decode(JWT::urlsafeB64Decode($payload), true);
        //$sig = JWT::urlsafeB64Decode($sig);

        return [$header, $payload, $sig];
    }
}
