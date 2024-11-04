<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\App\Bsky;

interface Actor
{
    /**
     * Get private preferences attached to the current account. Expected use is synchronization between multiple devices, and import/export during account migration. Requires auth.
     *
     * method: get
     */
    public function getPreferences();

    /**
     * Get detailed profile view of an actor. Does not require auth, but contains relevant metadata with auth.
     *
     * method: get
     */
    public function getProfile(string $actor);

    /**
     * Get detailed profile views of multiple actors.
     *
     * method: get
     */
    public function getProfiles(array $actors);

    /**
     * Get a list of suggested actors. Expected use is discovery of accounts to follow during new account onboarding.
     *
     * method: get
     */
    public function getSuggestions(?int $limit = 50, ?string $cursor = null);

    /**
     * Set the private preferences attached to the account.
     *
     * method: post
     */
    public function putPreferences(array $preferences);

    /**
     * Find actors (profiles) matching search criteria. Does not require auth.
     *
     * method: get
     */
    public function searchActors(?string $term = null, ?string $q = null, ?int $limit = 25, ?string $cursor = null);

    /**
     * Find actor suggestions for a prefix search term. Expected use is for auto-completion during text field entry. Does not require auth.
     *
     * method: get
     */
    public function searchActorsTypeahead(?string $term = null, ?string $q = null, ?int $limit = 10);
}
