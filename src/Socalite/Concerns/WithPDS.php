<?php

namespace Revolution\Bluesky\Socalite\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

trait WithPDS
{
    protected ?array $pds_protected_resource_meta = [];

    protected function pdsProtectedResourceMeta(string $pds_url, string $key = '', ?string $default = null): array|string|null
    {
        if (empty($this->pds_resource_meta)) {
            $this->pds_protected_resource_meta = Http::baseUrl($pds_url)
                ->get('/.well-known/oauth-protected-resource')
                ->json();

            if (Arr::get($this->pds_protected_resource_meta, 'resource') !== $pds_url) {
                throw new RuntimeException('Invalid PDS url.');
            }
        }

        if (empty($key)) {
            return $this->pds_protected_resource_meta;
        }

        return Arr::get($this->pds_protected_resource_meta, $key, $default);
    }

    protected function isSafeUrl(string $url): bool
    {
        if (app()->runningUnitTests()) {
            return true;
        }

        $url = filter_var($url, FILTER_VALIDATE_URL);
        if (! $url) {
            return false;
        }

        $url = parse_url($url);
        if (! $url) {
            return false;
        }

        info('url', $url);

        return Validator::make($url, [
            'scheme' => ['required', Rule::in(['https'])],
            'hostname' => ['required', 'string', Rule::notIn(['localhost'])],
            'port' => 'missing',
            'username' => 'missing',
            'password' => 'missing',
        ])->passes();
    }
}
