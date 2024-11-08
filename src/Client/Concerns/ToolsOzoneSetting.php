<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Setting;

trait ToolsOzoneSetting
{
    public function listOptions(?int $limit = 50, ?string $cursor = null, ?string $scope = 'instance', ?string $prefix = null, ?array $keys = null): Response
    {
        return $this->call(
            api: Setting::listOptions,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function removeOptions(array $keys, string $scope): Response
    {
        return $this->call(
            api: Setting::removeOptions,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function upsertOption(string $key, string $scope, mixed $value, ?string $description = null, ?string $managerRole = null): Response
    {
        return $this->call(
            api: Setting::upsertOption,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
