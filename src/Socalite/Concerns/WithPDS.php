<?php

namespace Revolution\Bluesky\Socalite\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

trait WithPDS
{
    protected array $pds_protected_resource_meta = [];

    protected function pdsProtectedResourceMeta(string $pds_url, string $key = '', ?string $default = null): array|string|null
    {
        if (empty($this->pds_resource_meta)) {
            $this->pds_protected_resource_meta = Http::get($pds_url.'/.well-known/oauth-protected-resource')
                ->json();
        }

        if (empty($key)) {
            return $this->pds_protected_resource_meta;
        }

        return Arr::get($this->pds_protected_resource_meta, $key, $default);
    }
}
