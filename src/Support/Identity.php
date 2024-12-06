<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Revolution\Bluesky\Facades\Bluesky;

/**
 * @link https://github.com/bluesky-social/cookbook/blob/main/python-oauth-web-app/atproto_identity.py
 */
class Identity
{
    // "***.bsky.social" "alice.test"
    protected const HANDLE_REGEX = '/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/';

    // "did:plc:1234..." "did:web:alice.test"
    protected const DID_REGEX = '/^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$/';

    protected const PLC_DIRECTORY = 'https://plc.directory';

    public const CACHE_HANDLE = 'bluesky:resolve-handle:';

    public const CACHE_DID = 'bluesky:resolve-did:';

    public static function isHandle(?string $handle): bool
    {
        return preg_match(self::HANDLE_REGEX, $handle ?? '') === 1;
    }

    public static function isDID(?string $did): bool
    {
        return preg_match(self::DID_REGEX, $did ?? '') === 1;
    }

    /**
     * @param  string|null  $id  handle or DID.  e.g. "alice.test" "did:plc:1234..." "did:web:alice.test"
     * @return Response{id: string, alsoKnownAs: array, verificationMethod: array, service: array} didDoc
     */
    public function resolveIdentity(?string $id, bool $cache = true): Response
    {
        return match (true) {
            self::isHandle($id) => $this->resolveDID($this->resolveHandle($id, $cache), $cache),
            self::isDID($id) => $this->resolveDID($id, $cache),
            default => throw new InvalidArgumentException('Invalid ID provided.'),
        };
    }

    /**
     * Get the handle's did directly from the DNS record or well-known.
     *
     * ```
     * $did = Bluesky::identity()->resolveHandle('alice.test');
     * ```
     *
     * {@link Bluesky::resolveHandle()} is an alternative way to use the API.
     *
     * @param  string|null  $handle  e.g. "alice.test"
     * @return string|null DID
     */
    public function resolveHandle(?string $handle, bool $cache = true): ?string
    {
        if (! self::isHandle($handle) || ! Str::isUrl('https://'.$handle, ['https'])) {
            throw new InvalidArgumentException("The handle '$handle' is not a valid handle."); // @codeCoverageIgnore
        }

        if ($cache && cache()->has(self::CACHE_HANDLE.$handle)) {
            return cache(self::CACHE_HANDLE.$handle);
        }

        // check DNS TXT record
        $record = DNS::record('_atproto.'.$handle);

        $did = collect($record)->pluck('txt')->first(function ($txt) {
            return Str::startsWith($txt, 'did=did:');
        });

        $did = Str::chopStart($did, 'did=');

        if (self::isDID($did)) {
            if ($cache) {
                cache()->put(self::CACHE_HANDLE.$handle, $did, now()->addDay());
            }

            return $did;
        }

        // check well-known
        $response = Http::timeout(5)->get("https://$handle/.well-known/atproto-did");
        $did = trim($response->body());

        if (self::isDID($did)) {
            if ($cache) {
                cache()->put(self::CACHE_HANDLE.$handle, $did, now()->addDay());
            }

            return $did;
        }

        return null; // @codeCoverageIgnore
    }

    /**
     * resolveDID.
     *
     * ```
     * $didDoc = Bluesky::identity()->resolveDID('did:plc:***')->json();
     * ```
     *
     * @param  string|null  $did  e.g. "did:plc:1234..." "did:web:alice.test"
     * @return Response{id: string, alsoKnownAs: array, verificationMethod: array, service: array} didDoc
     *
     * @link https://plc.directory/did:plc:ewvi7nxzyoun6zhxrhs64oiz
     */
    public function resolveDID(?string $did, bool $cache = true): Response
    {
        if (! self::isDID($did)) {
            throw new InvalidArgumentException("The did '$did' is not a valid DID.");
        }

        if ($cache && cache()->has(self::CACHE_DID.$did)) {
            return new Response(Http::response(cache(self::CACHE_DID.$did))->wait());
        }

        $url = match (true) {
            Str::startsWith($did, 'did:plc:') => Str::rtrim(config('bluesky.plc') ?? self::PLC_DIRECTORY, '/').'/'.$did,
            Str::startsWith($did, 'did:web:') => 'https://'.Str::remove('did:web:', $did).'/.well-known/did.json',
            default => throw new InvalidArgumentException('Unsupported DID type'),
        };

        $response = Http::get($url);

        if ($cache && $response->successful()) {
            cache()->put(self::CACHE_DID.$did, $response->json(), now()->addDay());
        }

        return $response;
    }
}
