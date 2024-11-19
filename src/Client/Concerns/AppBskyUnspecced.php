<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Unspecced;

trait AppBskyUnspecced
{
    public function getConfig(): Response
    {
        return $this->call(
            api: Unspecced::getConfig,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getPopularFeedGenerators(?int $limit = 50, ?string $cursor = null, ?string $query = null): Response
    {
        return $this->call(
            api: Unspecced::getPopularFeedGenerators,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getSuggestionsSkeleton(?string $viewer = null, ?int $limit = 50, ?string $cursor = null, ?string $relativeToDid = null): Response
    {
        return $this->call(
            api: Unspecced::getSuggestionsSkeleton,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getTaggedSuggestions(): Response
    {
        return $this->call(
            api: Unspecced::getTaggedSuggestions,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function searchActorsSkeleton(string $q, ?string $viewer = null, ?bool $typeahead = null, ?int $limit = 25, ?string $cursor = null): Response
    {
        return $this->call(
            api: Unspecced::searchActorsSkeleton,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function searchPostsSkeleton(string $q, ?string $sort = 'latest', ?string $since = null, ?string $until = null, ?string $mentions = null, ?string $author = null, ?string $lang = null, ?string $domain = null, ?string $url = null, ?array $tag = null, ?string $viewer = null, ?int $limit = 25, ?string $cursor = null): Response
    {
        return $this->call(
            api: Unspecced::searchPostsSkeleton,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function searchStarterPacksSkeleton(string $q, ?string $viewer = null, ?int $limit = 25, ?string $cursor = null): Response
    {
        return $this->call(
            api: Unspecced::searchStarterPacksSkeleton,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }
}
