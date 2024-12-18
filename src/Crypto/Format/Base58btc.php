<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Crypto\Format;

use Illuminate\Support\Str;
use InvalidArgumentException;
use phpseclib3\Crypt\EC\BaseCurves\Base;
use phpseclib3\Crypt\EC\BaseCurves\Prime;
use phpseclib3\Crypt\EC\Formats\Keys\Common;
use phpseclib3\Math\PrimeField\Integer as PrimeInteger;
use Revolution\Bluesky\Crypto\DidKey;
use Revolution\Bluesky\Crypto\K256;
use Revolution\Bluesky\Crypto\P256;
use YOCLIB\Multiformats\Multibase\Multibase;

/**
 * phpseclib custom key format.
 *
 * Supports both loading and saving base58btc encoded public key.
 */
final class Base58btc
{
    use Common;

    /**
     * @param  array{0: PrimeInteger, 1: PrimeInteger}  $publicKey
     */
    public static function savePublicKey(Base $curve, array $publicKey, array $options = []): string
    {
        $compressed = Compress::savePublicKey($curve, $publicKey);

        $alg_prefix = match (class_basename($curve::class)) {
            P256::CURVE => P256::MULTIBASE_PREFIX,
            K256::CURVE => K256::MULTIBASE_PREFIX,
            default => throw new InvalidArgumentException('Unsupported format.'),
        };

        return Multibase::encode(Multibase::BASE58BTC, $alg_prefix.hex2bin($compressed));
    }

    public static function load($key, $password = ''): array
    {
        // did:key:z***
        $key = Str::chopStart($key, DidKey::PREFIX);

        // decode base58btc
        $keyBytes = Multibase::decode($key);

        $alg_prefix = substr($keyBytes, offset: 0, length: 2);

        $curve_name = match ($alg_prefix) {
            P256::MULTIBASE_PREFIX => P256::CURVE,
            K256::MULTIBASE_PREFIX => K256::CURVE,
            default => throw new InvalidArgumentException('Unsupported format.'),
        };

        // remove alg prefix
        $keyBytes = substr($keyBytes, offset: 2, length: null);

        if (strlen($keyBytes) !== 33) {
            throw new InvalidArgumentException('compressed key length must be 33.');
        }

        /** @var Prime $curve */
        $curve = self::loadCurveByParam(['namedCurve' => $curve_name]);

        /** @var array{0: PrimeInteger, 1: PrimeInteger} $point */
        $point = self::extractPoint("\0".$keyBytes, $curve);

        throw_unless($curve->verifyPoint($point));

        return [
            'curve' => $curve,
            'QA' => $point,
        ];
    }
}
