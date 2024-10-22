<?php

namespace Revolution\Bluesky\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Revolution\Bluesky\Facades\Bluesky;

final class DidDocument implements Arrayable
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

    public static function create(array|Collection|null $didDoc = null): self
    {
        return new self($didDoc);
    }

    public function fetch(?string $did = null): self
    {
        $did = $did ?? $this->id();

        if (empty($did)) {
            return $this;
        }

        $response = Bluesky::identity()->resolveDID($did);

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

    public function endpoint(?string $default = null): ?string
    {
        $service = collect($this->didDoc->get('service', []))
            ->firstWhere('id', '#atproto_pds');

        return data_get($service, 'serviceEndpoint', $default);
    }

    public function get(string $key, ?string $default = null): mixed
    {
        return data_get($this->didDoc, $key, $default);
    }

    public function __get(string $key)
    {
        return data_get($this->didDoc, $key);
    }

    public function __set($name, $value)
    {
        $this->didDoc->put($name, $value);
    }

    public function toArray(): array
    {
        return $this->didDoc->toArray();
    }
}
