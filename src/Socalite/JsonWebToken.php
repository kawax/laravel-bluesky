<?php

namespace Revolution\Bluesky\Socalite;

use Firebase\JWT\JWT;
use phpseclib3\Crypt\EC\PrivateKey;

class JsonWebToken
{
    public static function encode(
        array $head,
        array $payload,
        PrivateKey $key,
    ): string {
        return JWT::encode(
            payload: $payload,
            key: $key->toString('PKCS8'),
            alg: JsonWebKey::ALG,
            head: $head,
        );
    }
}
