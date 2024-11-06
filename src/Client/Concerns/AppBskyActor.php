<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Actor;

trait AppBskyActor
{
    public function getPreferences(): Response
    {
        return $this->call(
            api: Actor::getPreferences,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getProfile(string $actor): Response
    {
        return $this->call(
            api: Actor::getProfile,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getProfiles(array $actors): Response
    {
        return $this->call(
            api: Actor::getProfiles,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getSuggestions(?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Actor::getSuggestions,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function putPreferences(array $preferences): Response
    {
        return $this->call(
            api: Actor::putPreferences,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function searchActors(?string $term = null, ?string $q = null, ?int $limit = 25, ?string $cursor = null): Response
    {
        return $this->call(
            api: Actor::searchActors,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function searchActorsTypeahead(?string $term = null, ?string $q = null, ?int $limit = 10): Response
    {
        return $this->call(
            api: Actor::searchActorsTypeahead,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }
}
