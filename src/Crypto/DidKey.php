<?php

namespace Revolution\Bluesky\Crypto;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use phpseclib3\Crypt\EC;
use Revolution\Bluesky\Crypto\Format\Base58btc;

/**
 * ```
 * // did:key: format
 *
 * |did:key:|multibase prefix|(alg prefix|compressed public key)|
 * ```
 *
 * @link https://github.com/bluesky-social/atproto/blob/main/packages/crypto/src/did.ts
 * @link https://atproto.com/specs/cryptography
 */
class DidKey
{
    public const PREFIX = 'did:key:';

    protected const ALGS = [
        P256::CURVE => P256::ALG,
        K256::CURVE => K256::ALG,
    ];

    /**
     * ```
     * use Revolution\Bluesky\Crypto\DidKey;
     *
     * $didkey = DidKey::parse('z***');
     *
     * [
     *     'curve' => 'secp256k1',
     *     'alg' => 'ES256K',
     *     'key' => 'Public key PEM format',
     * ]
     * ```
     *
     * @param  string  $didkey  `did:key:z***` or `z***`
     * @return array{curve: string, alg: string, key: string}
     */
    public static function parse(string $didkey): array
    {
        EC::addFileFormat(Base58btc::class);

        $key = EC::loadPublicKeyFormat(class_basename(Base58btc::class), $didkey);

        $curve = $key->getCurve();

        if (! Arr::except(self::ALGS, $curve)) {
            throw new InvalidArgumentException('Unsupported format.');
        }

        return [
            'curve' => $curve,
            'alg' => self::ALGS[$curve],
            'key' => (string) $key,
        ];
    }

    /**
     * ```
     * use Revolution\Bluesky\Crypto\DidKey;
     *
     * $didkey = DidKey::encode('Public key PEM format');
     *
     * // z***
     * ```
     *
     * @param  string  $pubkey  Public key PEM
     * @return string `z***`
     */
    public static function encode(string $pubkey): string
    {
        EC::addFileFormat(Base58btc::class);

        $key = EC::loadPublicKey($pubkey);

        return $key->toString(class_basename(Base58btc::class));
    }

    /**
     * ```
     * use Revolution\Bluesky\Crypto\DidKey;
     *
     * $didkey = DidKey::format('Public key PEM format');
     *
     * // did:key:z***
     * ```
     *
     * @param  string  $pubkey  Public key PEM
     * @return string `did:key:z***`
     */
    public static function format(string $pubkey): string
    {
        return self::PREFIX.self::encode($pubkey);
    }
}
