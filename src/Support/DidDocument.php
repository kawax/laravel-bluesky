<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Revolution\Bluesky\Crypto\DidKey;

/**
 * @link https://plc.directory/did:plc:ewvi7nxzyoun6zhxrhs64oiz
 */
final readonly class DidDocument implements Arrayable
{
    protected Collection $didDoc;

    public function __construct(array|Collection|null $didDoc = null)
    {
        $this->didDoc = Collection::wrap($didDoc)
            ->only([
                '@context',
                'id',
                'alsoKnownAs',
                'verificationMethod',
                'service',
            ]);
    }

    public static function make(array|Collection|null $didDoc = null): self
    {
        return new self($didDoc);
    }

    public function id(): string
    {
        return data_get($this->didDoc, 'id', '');
    }

    public function handle(): string
    {
        $handle = data_get($this->didDoc, 'alsoKnownAs.{first}', '');

        return Str::chopStart($handle, 'at://');
    }

    /**
     * PDS url.
     *
     * @return string `https://***.***.host.bsky.network`
     */
    public function pdsUrl(string $default = ''): string
    {
        $service = collect((array) $this->didDoc->get('service', []))
            ->firstWhere('id', '#atproto_pds');

        return data_get($service, 'serviceEndpoint', $default);
    }

    /**
     * Get "aud" for Service Auth from PDS url.
     *
     * @return string `did:web:***.***.host.bsky.network`
     */
    public function serviceAuthAud(): string
    {
        return Str::replace(search: 'https://', replace: 'did:web:', subject: (string) $this->pdsUrl());
    }

    /**
     * Get public key.
     *
     * This key can be decoded with {@link DidKey::parse()}.
     *
     * ```
     * use Revolution\Bluesky\Crypto\DidKey;
     *
     * $pubkey = $didDoc->publicKey();
     *
     * $parsed = DidKey::parse($pubkey);
     * $pubkey_pem = $parsed->key;
     * ```
     */
    public function publicKey(string $default = ''): string
    {
        $verification = collect((array) $this->didDoc->get('verificationMethod', []))
            ->firstWhere('type', 'Multikey');

        return data_get($verification, 'publicKeyMultibase', $default);
    }

    public function get(string $key, ?string $default = null): mixed
    {
        return data_get($this->didDoc, $key, $default);
    }

    public function toArray(): array
    {
        return $this->didDoc->toArray();
    }
}
