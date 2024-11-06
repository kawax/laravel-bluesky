<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Set;
use Revolution\Bluesky\Client\HasHttp;

trait ToolsOzoneSet
{
    use HasHttp;

    public function addValues(string $name, array $values): Response
    {
        return $this->call(
            api: Set::addValues,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function deleteSet(string $name): Response
    {
        return $this->call(
            api: Set::deleteSet,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function deleteValues(string $name, array $values): Response
    {
        return $this->call(
            api: Set::deleteValues,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getValues(string $name, ?int $limit = 100, ?string $cursor = null): Response
    {
        return $this->call(
            api: Set::getValues,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function querySets(?int $limit = 50, ?string $cursor = null, ?string $namePrefix = null, ?string $sortBy = 'name', ?string $sortDirection = 'asc'): Response
    {
        return $this->call(
            api: Set::querySets,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function upsertSet(): Response
    {
        return $this->call(
            api: Set::upsertSet,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
