<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socalite\Key;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use phpseclib3\Crypt\EC\PrivateKey;

final class JsonWebToken
{
    public static function encode(
        array $head,
        array $payload,
        PrivateKey $key,
    ): string {
        return JWT::encode(
            payload: $payload,
            key: $key->toString(BlueskyKey::TYPE),
            alg: JsonWebKey::ALG,
            head: $head,
        );
    }

    /**
     * Get payload from JsonWebToken.
     *
     * ```
     * $jwt = JsonWebToken::decode('****.****.****');
     *
     * [
     *     'header' => [],
     *     'payload' => [],
     *     'sig' => 'Remains url-safe base64 encoded',
     * ]
     * ```
     * ```
     * // Decode sig
     * use Firebase\JWT\JWT;
     *
     * $sig = JWT::urlsafeB64Decode($sig);
     * ```
     *
     * @return null|array{header: array, payload: array, sig: string}
     */
    public static function decode(?string $token): ?array
    {
        if (Str::substrCount($token, '.') !== 2) {
            return null;
        }

        [$header, $payload, $sig] = explode('.', $token, 3);

        $header = json_decode(JWT::urlsafeB64Decode($header), true);
        $payload = json_decode(JWT::urlsafeB64Decode($payload), true);
        //$sig = JWT::urlsafeB64Decode($sig);

        return compact('header', 'payload', 'sig');
    }
}
