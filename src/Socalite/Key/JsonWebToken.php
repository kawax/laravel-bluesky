<?php

namespace Revolution\Bluesky\Socalite\Key;

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
            key: $key->toString(BlueskyKey::TYPE),
            alg: JsonWebKey::ALG,
            head: $head,
        );
    }
}
