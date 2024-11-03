<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Contracts\Lexicon\App\Bsky;

interface Unspecced
{
    /**
     * Get miscellaneous runtime configuration.
     *
     * method: get
     */
    public function getConfig();

    /**
     * An unspecced view of globally popular feed generators.
     *
     * method: get
     */
    public function getPopularFeedGenerators(?int $limit = 50, ?string $cursor = null, ?string $query = null);

    /**
     * Get a skeleton of suggested actors. Intended to be called and then hydrated through app.bsky.actor.getSuggestions
     *
     * method: get
     */
    public function getSuggestionsSkeleton(?string $viewer = null, ?int $limit = 50, ?string $cursor = null, ?string $relativeToDid = null);

    /**
     * Get a list of suggestions (feeds and users) tagged with categories
     *
     * method: get
     */
    public function getTaggedSuggestions();

    /**
     * Backend Actors (profile) search, returns only skeleton.
     *
     * method: get
     */
    public function searchActorsSkeleton(string $q, ?string $viewer = null, ?bool $typeahead = null, ?int $limit = 25, ?string $cursor = null);

    /**
     * Backend Posts search, returns only skeleton
     *
     * method: get
     */
    public function searchPostsSkeleton(string $q, ?string $sort = 'latest', ?string $since = null, ?string $until = null, ?string $mentions = null, ?string $author = null, ?string $lang = null, ?string $domain = null, ?string $url = null, ?array $tag = null, ?string $viewer = null, ?int $limit = 25, ?string $cursor = null);
}
