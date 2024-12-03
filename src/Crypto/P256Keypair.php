<?php

namespace Revolution\Bluesky\Crypto;

class P256Keypair extends AbstractKeypair
{
    public const CURVE = 'secp256r1';

    public const ALG = 'ES256';

    public const MULTIBASE_PREFIX = "\x80\x24";
}
