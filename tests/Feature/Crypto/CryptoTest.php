<?php

namespace Tests\Feature\Crypto;

use Mdanter\Ecc\Curves\CurveFactory;
use PHPUnit\Framework\Attributes\RequiresMethod;
use Revolution\Bluesky\Crypto\DidKey;
use Revolution\Bluesky\Crypto\K256;
use Tests\TestCase;

class CryptoTest extends TestCase
{
    #[RequiresMethod(CurveFactory::class, 'getGeneratorByName')]
    public function test_did_key_parse(): void
    {
        // https://plc.directory/did:plc:ewvi7nxzyoun6zhxrhs64oiz

        $parsed = DidKey::parse('zQ3shunBKsXixLxKtC5qeSG9E4J5RkGN57im31pcTzbNQnm5w');

        $this->assertIsString($parsed['key']);
    }

    #[RequiresMethod(CurveFactory::class, 'getGeneratorByName')]
    public function test_did_key_encode(): void
    {
        $pubkey = K256::create()->publicPEM();

        $b58key = DidKey::encode($pubkey);
        $didkey = DidKey::format($pubkey);

        $parsed = DidKey::parse($b58key);

        $this->assertStringStartsWith('z', $b58key);
        $this->assertStringStartsWith('did:key:z', $didkey);
        $this->assertSame('ES256K', $parsed['alg']);
    }
}
