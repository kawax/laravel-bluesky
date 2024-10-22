<?php

namespace Revolution\Bluesky\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class DidDocument
{
    protected const BASE_URL = 'https://plc.directory';

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

    public static function create(array|Collection|null $didDoc = null): self
    {
        return new self(Collection::wrap($didDoc));
    }

    public function fetch(?string $did = null): self
    {
        $did = $did ?? $this->didDoc->get('id');
        if (empty($did)) {
            return $this;
        }

        $response = Http::baseUrl(self::BASE_URL)
            ->get($did);

        if ($response->successful()) {
            $this->didDoc = $response->collect();
        }

        return $this;
    }

    public function id(): ?string
    {
        return data_get($this->didDoc, 'id');
    }

    public function handle(): ?string
    {
        $handle = data_get($this->didDoc, 'alsoKnownAs.{first}');

        return Str::chopStart($handle, 'at://');
    }

    public function pdsEndpoint(?string $default = null): ?string
    {
        return collect($this->didDoc['service'] ?? [])
            ->firstWhere('id', '#atproto_pds')['serviceEndpoint'] ?? $default;
    }

    public function get(string $key, ?string $default = null): mixed
    {
        return data_get($this->didDoc, $key, $default);
    }
}
