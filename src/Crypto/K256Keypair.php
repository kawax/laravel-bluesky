<?php

namespace Revolution\Bluesky\Crypto;

class K256Keypair extends AbstractKeypair
{
    public const CURVE = 'secp256k1';

    public const ALG = 'ES256K';

    public const MULTIBASE_PREFIX = "\xe7\x01";
}
