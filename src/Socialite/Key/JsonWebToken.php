<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socialite\Key;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Revolution\Bluesky\Crypto\P256;

final class JsonWebToken
{
    /**
     * encode.
     *
     * ```
     * $head = ['type' => 'JWT', 'alg' => 'ES256'];
     * $payload = ['iss' => '', 'aud' => ''];
     * $key = 'Private key(PEM format)';
     * $jwt = JsonWebToken::encode(head: $head, payload: $payload, key: $key, alg: 'ES256');
     * ```
     *
     * @param  string  $key  Private key PEM
     * @return string JWT `****.****.****`
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
     * If you want to decode `$sig` as well, specify `decode: true`.
     * ```
     * [$header, $payload, $sig] = JsonWebToken::explode('****.****.****', decode: true);
     * ```
     *
     * @return null|array<array, array, string>
     */
    public static function explode(?string $token, bool $decode = false): ?array
    {
        if (Str::substrCount($token, '.') !== 2) {
            return null;
        }

        [$header, $payload, $sig] = explode(separator: '.', string: $token, limit: 3);

        $header = json_decode(JWT::urlsafeB64Decode($header), associative: true, flags: JSON_BIGINT_AS_STRING);
        $payload = json_decode(JWT::urlsafeB64Decode($payload), associative: true, flags: JSON_BIGINT_AS_STRING);
        if ($decode) {
            $sig = JWT::urlsafeB64Decode($sig);
        }

        return [$header, $payload, $sig];
    }
}
