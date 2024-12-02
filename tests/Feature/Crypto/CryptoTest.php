<?php

namespace Tests\Feature\Crypto;

use Mdanter\Ecc\Crypto\Key\PublicKey;
use Mdanter\Ecc\Curves\CurveFactory;
use Mdanter\Ecc\Curves\SecgCurve;
use Revolution\Bluesky\Crypto\DidKey;
use Tests\TestCase;

class CryptoTest extends TestCase
{
    public function test_did_key_parse(): void
    {
        // https://plc.directory/did:plc:ewvi7nxzyoun6zhxrhs64oiz

        $parsed = DidKey::parse('zQ3shunBKsXixLxKtC5qeSG9E4J5RkGN57im31pcTzbNQnm5w');

        $this->assertIsString($parsed['key']);
    }

    public function test_did_key_encode(): void
    {
        $generator = CurveFactory::getGeneratorByName(SecgCurve::NAME_SECP_256K1);

        $sk = $generator->createPrivateKey();
        $pubkey = $sk->getPublicKey();

        $b58key = DidKey::encode($pubkey);

        $parsed = DidKey::parse($b58key);

        $this->assertStringStartsWith('z', $b58key);
        $this->assertSame('ES256K', $parsed['alg']);
    }
}
