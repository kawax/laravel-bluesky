<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Crypto;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\EC\Formats\Keys\PKCS8;
use phpseclib3\Crypt\EC\PublicKey;
use Revolution\Bluesky\Crypto\Format\Base58btc;

/**
 * did key.
 *
 * ```
 * // did:key: format
 *
 * |did:key:|multibase prefix|(alg prefix|compressed public key)|
 * ```
 *
 * @link https://github.com/bluesky-social/atproto/blob/main/packages/crypto/src/did.ts
 * @link https://atproto.com/specs/cryptography
 */
final class DidKey implements Arrayable, ArrayAccess
{
    public const PREFIX = 'did:key:';

    protected const ALGS = [
        P256::CURVE => P256::ALG,
        K256::CURVE => K256::ALG,
    ];

    public function __construct(
        public string $curve,
        public string $alg,
        public string $key,
    ) {
    }

    /**
     * Did key to Public key.
     *
     * ```
     * use Revolution\Bluesky\Crypto\DidKey;
     *
     * $didkey = DidKey::parse('z***')->toArray();
     *
     * [
     *     'curve' => 'secp256k1',
     *     'alg' => 'ES256K',
     *     'key' => 'Public key PEM format',
     * ]
     * ```
     *
     * @param  string  $didkey  `did:key:z***` or `z***`
     */
    public static function parse(string $didkey): self
    {
        EC::addFileFormat(Base58btc::class);

        /** @var PublicKey $key */
        $key = EC::loadPublicKeyFormat(class_basename(Base58btc::class), $didkey);

        $curve = (string) Collection::wrap($key->getCurve())->first();

        if (! Arr::exists(self::ALGS, $curve)) {
            throw new InvalidArgumentException('Unsupported format.');
        }

        return new self($curve, self::ALGS[$curve], $key->toString(class_basename(PKCS8::class)));
    }

    /**
     * Encode public key.
     *
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
     * Public key to Did key.
     *
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

    /**
     * @return array{curve: string, alg: string, key: string}
     */
    public function toArray(): array
    {
        return [
            'curve' => $this->curve,
            'alg' => $this->alg,
            'key' => $this->key,
        ];
    }

    public function offsetExists(mixed $offset): bool
    {
        return Arr::exists($this->toArray(), $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return Arr::get($this->toArray(), $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (property_exists($this, $offset)) {
            $this->$offset = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if (property_exists($this, $offset)) {
            unset($this->$offset);
        }
    }
}
