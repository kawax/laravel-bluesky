<?php

declare(strict_types=1);

namespace Tests\Feature\Crypto;

use phpseclib3\Crypt\EC;
use Revolution\Bluesky\Crypto\DidKey;
use Revolution\Bluesky\Crypto\Format\Base58btc;
use Revolution\Bluesky\Crypto\K256;
use Tests\TestCase;

class CryptoTest extends TestCase
{
    public function test_did_key_parse(): void
    {
        // https://plc.directory/did:plc:ewvi7nxzyoun6zhxrhs64oiz

        $parsed = DidKey::parse('zQ3shunBKsXixLxKtC5qeSG9E4J5RkGN57im31pcTzbNQnm5w');

        $this->assertIsString($parsed['key']);
        $this->assertSame('secp256k1', $parsed['curve']);
        $this->assertSame('ES256K', $parsed['alg']);
    }

    public function test_did_key_encode(): void
    {
        $pubkey = K256::create()->publicPEM();

        $b58key = DidKey::encode($pubkey);
        $didkey = DidKey::format($pubkey);

        EC::addFileFormat(Base58btc::class);
        $key = EC::loadPublicKey($didkey);
        $b58key2 = $key->toString('Base58btc');

        $parsed = DidKey::parse($b58key);

        $this->assertStringStartsWith('z', $b58key);
        $this->assertStringStartsWith('did:key:z', $didkey);
        $this->assertSame('secp256k1', $parsed['curve']);
        $this->assertSame('ES256K', $parsed['alg']);
        $this->assertSame($b58key, $b58key2);
    }
}
