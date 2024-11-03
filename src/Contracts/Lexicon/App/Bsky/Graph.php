<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Contracts\Lexicon\App\Bsky;

interface Graph
{
    /**
     * Get a list of starter packs created by the actor.
     *
     * method: get
     */
    public function getActorStarterPacks(string $actor, ?int $limit = 50, ?string $cursor = null);

    /**
     * Enumerates which accounts the requesting account is currently blocking. Requires auth.
     *
     * method: get
     */
    public function getBlocks(?int $limit = 50, ?string $cursor = null);

    /**
     * Enumerates accounts which follow a specified account (actor).
     *
     * method: get
     */
    public function getFollowers(string $actor, ?int $limit = 50, ?string $cursor = null);

    /**
     * Enumerates accounts which a specified account (actor) follows.
     *
     * method: get
     */
    public function getFollows(string $actor, ?int $limit = 50, ?string $cursor = null);

    /**
     * Enumerates accounts which follow a specified account (actor) and are followed by the viewer.
     *
     * method: get
     */
    public function getKnownFollowers(string $actor, ?int $limit = 50, ?string $cursor = null);

    /**
     * Gets a 'view' (with additional context) of a specified list.
     *
     * method: get
     */
    public function getList(string $list, ?int $limit = 50, ?string $cursor = null);

    /**
     * Get mod lists that the requesting account (actor) is blocking. Requires auth.
     *
     * method: get
     */
    public function getListBlocks(?int $limit = 50, ?string $cursor = null);

    /**
     * Enumerates mod lists that the requesting account (actor) currently has muted. Requires auth.
     *
     * method: get
     */
    public function getListMutes(?int $limit = 50, ?string $cursor = null);

    /**
     * Enumerates the lists created by a specified account (actor).
     *
     * method: get
     */
    public function getLists(string $actor, ?int $limit = 50, ?string $cursor = null);

    /**
     * Enumerates accounts that the requesting account (actor) currently has muted. Requires auth.
     *
     * method: get
     */
    public function getMutes(?int $limit = 50, ?string $cursor = null);

    /**
     * Enumerates public relationships between one account, and a list of other accounts. Does not require auth.
     *
     * method: get
     */
    public function getRelationships(string $actor, ?array $others = null);

    /**
     * Gets a view of a starter pack.
     *
     * method: get
     */
    public function getStarterPack(string $starterPack);

    /**
     * Get views for a list of starter packs.
     *
     * method: get
     */
    public function getStarterPacks(array $uris);

    /**
     * Enumerates follows similar to a given account (actor). Expected use is to recommend additional accounts immediately after following one account.
     *
     * method: get
     */
    public function getSuggestedFollowsByActor(string $actor);

    /**
     * Creates a mute relationship for the specified account. Mutes are private in Bluesky. Requires auth.
     *
     * method: post
     */
    public function muteActor(string $actor);

    /**
     * Creates a mute relationship for the specified list of accounts. Mutes are private in Bluesky. Requires auth.
     *
     * method: post
     */
    public function muteActorList(string $list);

    /**
     * Mutes a thread preventing notifications from the thread and any of its children. Mutes are private in Bluesky. Requires auth.
     *
     * method: post
     */
    public function muteThread(string $root);

    /**
     * Unmutes the specified account. Requires auth.
     *
     * method: post
     */
    public function unmuteActor(string $actor);

    /**
     * Unmutes the specified list of accounts. Requires auth.
     *
     * method: post
     */
    public function unmuteActorList(string $list);

    /**
     * Unmutes the specified thread. Requires auth.
     *
     * method: post
     */
    public function unmuteThread(string $root);
}
