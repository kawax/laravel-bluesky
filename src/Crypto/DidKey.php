<?php

namespace Revolution\Bluesky\Crypto;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Mdanter\Ecc\Crypto\Key\PublicKey;
use Mdanter\Ecc\Crypto\Key\PublicKeyInterface;
use Mdanter\Ecc\Curves\CurveFactory;
use Mdanter\Ecc\Curves\SecgCurve;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Serializer\Point\CompressedPointSerializer;
use Mdanter\Ecc\Serializer\PublicKey\DerPublicKeySerializer;
use Mdanter\Ecc\Serializer\PublicKey\PemPublicKeySerializer;
use RuntimeException;
use YOCLIB\Multiformats\Multibase\Multibase;

/**
 * ```
 * // did:key: format
 *
 * |did:key:|(multibase prefix)|(alg prefix)|(compressed public key)|
 * ```
 * @link https://github.com/bluesky-social/atproto/blob/main/packages/crypto/src/did.ts
 */
class DidKey
{
    protected const DID_KEY_PREFIX = 'did:key:';

    protected const P256_DID_PREFIX = [0x80, 0x24];

    protected const SECP256K1_DID_PREFIX = [0xe7, 0x01];

    protected const ALGS = [
        'ES256' => SecgCurve::NAME_SECP_256R1,
        'ES256K' => SecgCurve::NAME_SECP_256K1,
    ];

    /**
     * @param  string  $didkey  `did:key:z***` or `z***`
     * @return array{alg: string, key: string}
     */
    public static function parse(string $didkey): array
    {
        if (! class_exists(EccFactory::class)) {
            throw new RuntimeException('Please install any ecc package.');
        }

        // did:key:z***
        $key = Str::chopStart($didkey, self::DID_KEY_PREFIX);

        // decode base58btc
        $keyBytes = Multibase::decode($key);

        $alg_prefix = substr($keyBytes, offset: 0, length: 2);

        $alg = match ($alg_prefix) {
            self::p256prefix() => 'ES256',
            self::k256prefix() => 'ES256K',
            default => throw new InvalidArgumentException('Unsupported format.'),
        };

        // remove alg prefix
        $keyBytes = substr($keyBytes, offset: 2, length: null);

        // compressed key length must be 33.
        if (strlen($keyBytes) !== 33) {
            throw new InvalidArgumentException();
        }

        // decompress
        $keyHex = bin2hex($keyBytes);
        $adapter = EccFactory::getAdapter();
        $generator = CurveFactory::getGeneratorByName(self::ALGS[$alg]);
        $compSerializer = new CompressedPointSerializer($adapter);
        $point = $compSerializer->unserialize($generator->getCurve(), $keyHex);

        $pubkey = new PublicKey($adapter, $generator, $point);

        $derSerializer = new DerPublicKeySerializer($adapter);
        $pemSerializer = new PemPublicKeySerializer($derSerializer);

        // pubkey pem
        $pem = $pemSerializer->serialize($pubkey);

        return [
            'alg' => $alg,
            'key' => $pem
        ];
    }

    public static function encode(PublicKeyInterface $pubkey): string
    {
        if (! class_exists(EccFactory::class)) {
            throw new RuntimeException('Please install any ecc package.');
        }

        $adapter = EccFactory::getAdapter();
        $serializer = new CompressedPointSerializer($adapter);
        $compressed = $serializer->serialize($pubkey->getPoint());

        $prefix = match ($pubkey->getCurve()->getName()) {
            SecgCurve::NAME_SECP_256R1 => self::p256prefix(),
            SecgCurve::NAME_SECP_256K1 => self::k256prefix(),
        };

        return Multibase::encode(Multibase::BASE58BTC, $prefix.hex2bin($compressed));
    }

    protected static function k256prefix(): string
    {
        return collect(self::SECP256K1_DID_PREFIX)->implode(fn ($value) => chr($value), '');
    }

    protected static function p256prefix(): string
    {
        return collect(self::P256_DID_PREFIX)->implode(fn ($value) => chr($value), '');
    }
}
