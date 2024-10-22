<?php

namespace Revolution\Bluesky\Socalite\Concerns;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Revolution\Bluesky\Facades\Bluesky;
use InvalidArgumentException;

trait WithPDS
{
    protected ?array $pds_protected_resource_meta = [];

    protected function updateServiceWithHint(): void
    {
        if (Str::startsWith($this->login_hint, 'https://') && $this->isSafeUrl($this->login_hint)) {
            $auth_url = $this->pdsProtectedResourceMeta($this->login_hint, 'authorization_servers.{first}', Bluesky::entryway());
            $this->service = Str::chopStart($auth_url, ['https://', 'http://']);

            $this->login_hint = null;
        }
    }

    protected function pdsProtectedResourceMeta(string $pds_url, string $key = '', ?string $default = null): array|string|null
    {
        if (empty($this->pds_resource_meta)) {
            $this->pds_protected_resource_meta = Bluesky::pds()
                ->resource($pds_url);

            if (data_get($this->pds_protected_resource_meta, 'resource') !== $pds_url) {
                throw new InvalidArgumentException('Invalid PDS url.');
            }

            $this->getOAuthSession()->put('pds', $this->pds_protected_resource_meta);
        }

        if (empty($key)) {
            return $this->pds_protected_resource_meta;
        }

        return data_get($this->pds_protected_resource_meta, $key, $default);
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

        return Validator::make($url, [
            'scheme' => ['required', Rule::in(['https'])],
            'host' => ['required', 'string', Rule::notIn(['localhost'])],
            'port' => 'missing',
            'user' => 'missing',
            'pass' => 'missing',
        ])->passes();
    }
}
