<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\App\Bsky;

interface Feed
{
    /**
     * Get information about a feed generator, including policies and offered feed URIs. Does not require auth; implemented by Feed Generator services (not App View).
     *
     * method: get
     */
    public function describeFeedGenerator();

    /**
     * Get a list of feeds (feed generator records) created by the actor (in the actor's repo).
     *
     * method: get
     */
    public function getActorFeeds(string $actor, ?int $limit = 50, ?string $cursor = null);

    /**
     * Get a list of posts liked by an actor. Requires auth, actor must be the requesting account.
     *
     * method: get
     */
    public function getActorLikes(string $actor, ?int $limit = 50, ?string $cursor = null);

    /**
     * Get a view of an actor's 'author feed' (post and reposts by the author). Does not require auth.
     *
     * method: get
     */
    public function getAuthorFeed(string $actor, ?int $limit = 50, ?string $cursor = null, ?string $filter = 'posts_with_replies', ?bool $includePins = null);

    /**
     * Get a hydrated feed from an actor's selected feed generator. Implemented by App View.
     *
     * method: get
     */
    public function getFeed(string $feed, ?int $limit = 50, ?string $cursor = null);

    /**
     * Get information about a feed generator. Implemented by AppView.
     *
     * method: get
     */
    public function getFeedGenerator(string $feed);

    /**
     * Get information about a list of feed generators.
     *
     * method: get
     */
    public function getFeedGenerators(array $feeds);

    /**
     * Get a skeleton of a feed provided by a feed generator. Auth is optional, depending on provider requirements, and provides the DID of the requester. Implemented by Feed Generator Service.
     *
     * method: get
     */
    public function getFeedSkeleton(string $feed, ?int $limit = 50, ?string $cursor = null);

    /**
     * Get like records which reference a subject (by AT-URI and CID).
     *
     * method: get
     */
    public function getLikes(string $uri, ?string $cid = null, ?int $limit = 50, ?string $cursor = null);

    /**
     * Get a feed of recent posts from a list (posts and reposts from any actors on the list). Does not require auth.
     *
     * method: get
     */
    public function getListFeed(string $list, ?int $limit = 50, ?string $cursor = null);

    /**
     * Get posts in a thread. Does not require auth, but additional metadata and filtering will be applied for authed requests.
     *
     * method: get
     */
    public function getPostThread(string $uri, ?int $depth = 6, ?int $parentHeight = 80);

    /**
     * Gets post views for a specified list of posts (by AT-URI). This is sometimes referred to as 'hydrating' a 'feed skeleton'.
     *
     * method: get
     */
    public function getPosts(array $uris);

    /**
     * Get a list of quotes for a given post.
     *
     * method: get
     */
    public function getQuotes(string $uri, ?string $cid = null, ?int $limit = 50, ?string $cursor = null);

    /**
     * Get a list of reposts for a given post.
     *
     * method: get
     */
    public function getRepostedBy(string $uri, ?string $cid = null, ?int $limit = 50, ?string $cursor = null);

    /**
     * Get a list of suggested feeds (feed generators) for the requesting account.
     *
     * method: get
     */
    public function getSuggestedFeeds(?int $limit = 50, ?string $cursor = null);

    /**
     * Get a view of the requesting account's home timeline. This is expected to be some form of reverse-chronological feed.
     *
     * method: get
     */
    public function getTimeline(?string $algorithm = null, ?int $limit = 50, ?string $cursor = null);

    /**
     * Find posts matching search criteria, returning views of those posts.
     *
     * method: get
     */
    public function searchPosts(string $q, ?string $sort = 'latest', ?string $since = null, ?string $until = null, ?string $mentions = null, ?string $author = null, ?string $lang = null, ?string $domain = null, ?string $url = null, ?array $tag = null, ?int $limit = 25, ?string $cursor = null);

    /**
     * Send information about interactions with feed items back to the feed generator that served them.
     *
     * method: post
     */
    public function sendInteractions(array $interactions);
}
