<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Identity
{
    // "alice.test"
    protected const HANDLE_REGEX = '/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/';

    // "did:plc:1234..." "did:web:alice.test"
    protected const DID_REGEX = '/^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$/';

    protected const PLC_DIRECTORY = 'https://plc.directory';

    public static function isHandle(?string $handle): bool
    {
        return ! Str::endsWith($handle, '.test')
            && preg_match(self::HANDLE_REGEX, $handle ?? '') === 1;
    }

    public static function isDID(?string $did): bool
    {
        return preg_match(self::DID_REGEX, $did ?? '') === 1;
    }

    /**
     * @param  string|null  $id  handle or DID.  e.g. "alice.test" "did:plc:1234..." "did:web:alice.test"
     */
    public function resolveIdentity(?string $id): Response
    {
        return match (true) {
            self::isHandle($id) => $this->resolveDid($this->resolveHandle($id)),
            self::isDID($id) => $this->resolveDid($id),
            default => throw new InvalidArgumentException('Invalid ID provided.'),
        };
    }

    /**
     * @param  string|null  $handle  e.g. "alice.test"
     */
    public function resolveHandle(?string $handle): ?string
    {
        if (! self::isHandle($handle)) {
            throw new InvalidArgumentException("The handle '$handle' is not a valid handle."); // @codeCoverageIgnore
        }

        // check DND TXT record
        $record = app(DNS::class)->record('_atproto.'.$handle);

        $did = collect($record)->pluck('txt')->first(function ($txt) {
            return Str::startsWith($txt, 'did=did:');
        });

        $did = Str::chopStart($did, 'did=');

        if (self::isDID($did)) {
            return $did;
        }

        // check well-known
        $response = Http::get("https://$handle/.well-known/atproto-did");
        $did = trim($response->body());

        if (self::isDID($did)) {
            return $did;
        }

        return null; // @codeCoverageIgnore
    }

    /**
     * @param  string|null  $did  e.g. "did:plc:1234..." "did:web:alice.test"
     */
    public function resolveDID(?string $did): Response
    {
        if (! self::isDID($did)) {
            throw new InvalidArgumentException("The did '$did' is not a valid DID.");
        }

        $url = match (true) {
            Str::startsWith($did, 'did:plc:') => Str::rtrim(config('bluesky.plc') ?? self::PLC_DIRECTORY, '/').'/'.$did,
            Str::startsWith($did, 'did:web:') => 'https://'.Str::remove('did:web:', $did).'/.well-known/did.json',
            default => throw new InvalidArgumentException('Unsupported DID type'),
        };

        return Http::get($url);
    }
}
