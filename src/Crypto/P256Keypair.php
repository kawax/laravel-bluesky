<?php

namespace Revolution\Bluesky\Crypto;

class P256Keypair extends AbstractKeypair
{
    protected const CURVE = 'secp256r1';

    public const ALG = 'ES256';
}
