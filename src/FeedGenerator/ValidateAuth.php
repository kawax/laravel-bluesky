<?php

namespace Revolution\Bluesky\FeedGenerator;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Mdanter\Ecc\EccFactory;
use Revolution\Bluesky\Crypto\DidKey;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Socialite\Key\JsonWebToken;
use Revolution\Bluesky\Support\DidDocument;
use Revolution\Bluesky\Support\Identity;

/**
 * @link https://github.com/bluesky-social/feed-generator/blob/main/src/auth.ts
 */
class ValidateAuth
{
    /**
     * @return string|null User's did
     */
    public function __invoke(?string $jwt, Request $request): ?string
    {
        [$header, $payload] = JsonWebToken::explode($jwt);

        $did = data_get($payload, 'iss');

        // Used only when any ecc package is installed.
        // Since there are multiple forked packages,
        // the user can decide which one to use.
        if (! class_exists(EccFactory::class)) {
            return $did;
        }

        if (! Identity::isDID($did)) {
            return null;
        }

        $didKey = cache()->remember(
            key: 'bluesky:did:key:'.$did,
            ttl: now()->addDay(),
            callback: fn () => DidKey::parse(DidDocument::make(Bluesky::identity()->resolveDID($did)->json())->publicKey())
        );

        $key = new Key($didKey['key'], $didKey['alg']);

        $payload = rescue(fn () => JWT::decode($jwt, $key));

        return $payload?->iss;
    }
}
