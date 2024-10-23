<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socalite\Concerns;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Revolution\Bluesky\Facades\Bluesky;
use InvalidArgumentException;
use Revolution\Bluesky\Support\DidDocument;
use Revolution\Bluesky\Support\Identity;
use Revolution\Bluesky\Support\ProtectedResource;

trait WithPDS
{
    protected ?ProtectedResource $pds_resource = null;

    protected function updateServiceWithHint(): void
    {
        if (Str::startsWith($this->login_hint, 'https://') && $this->isSafeUrl($this->login_hint)) {
            $auth_url = $this->pdsProtectedResource($this->login_hint)
                ->authServer(Bluesky::entryway());

            $this->service = Str::chopStart($auth_url, ['https://', 'http://']);

            $this->login_hint = null;
        }

        if (Identity::isDID($this->login_hint) || Identity::isHandle($this->login_hint)) {
            $didDoc = DidDocument::create(Bluesky::identity()->resolveIdentity($this->login_hint)->collect());

            $auth_url = $this->pdsProtectedResource($didDoc->pdsUrl())
                ->authServer(Bluesky::entryway());

            $this->service = Str::chopStart($auth_url, ['https://', 'http://']);
        }
    }

    protected function pdsProtectedResource(string $pds_url, string $key = '', ?string $default = null): ProtectedResource|array|string|null
    {
        if (empty($this->pds_resource)) {
            $this->pds_resource = Bluesky::pds()->resource($pds_url);

            if ($this->pds_resource->resource() !== $pds_url) {
                throw new InvalidArgumentException('Invalid PDS url.');
            }

            $this->getOAuthSession()->put('pds', $this->pds_resource->toArray());
        }

        if (empty($key)) {
            return $this->pds_resource;
        }

        return $this->pds_resource->get($key, $default);
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
