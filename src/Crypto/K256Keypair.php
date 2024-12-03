<?php

namespace Revolution\Bluesky\Crypto;

class K256Keypair extends AbstractKeypair
{
    protected const CURVE = 'secp256k1';

    public const ALG = 'ES256K';
}
