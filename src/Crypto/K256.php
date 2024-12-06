<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Crypto;

class K256 extends AbstractKeypair
{
    public const CURVE = 'secp256k1';

    public const ALG = 'ES256K';

    public const MULTIBASE_PREFIX = "\xe7\x01";
}
