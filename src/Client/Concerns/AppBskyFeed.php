<?php

/**
 * GENERATED CODE.
 */

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Feed;

trait AppBskyFeed
{
    public function describeFeedGenerator(): Response
    {
        return $this->call(
            api: Feed::describeFeedGenerator,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getActorFeeds(string $actor, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Feed::getActorFeeds,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getActorLikes(string $actor, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Feed::getActorLikes,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getAuthorFeed(string $actor, ?int $limit = 50, ?string $cursor = null, ?string $filter = 'posts_with_replies', ?bool $includePins = null): Response
    {
        return $this->call(
            api: Feed::getAuthorFeed,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getFeed(string $feed, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Feed::getFeed,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getFeedGenerator(string $feed): Response
    {
        return $this->call(
            api: Feed::getFeedGenerator,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getFeedGenerators(array $feeds): Response
    {
        return $this->call(
            api: Feed::getFeedGenerators,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getFeedSkeleton(string $feed, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Feed::getFeedSkeleton,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getLikes(string $uri, ?string $cid = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Feed::getLikes,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getListFeed(string $list, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Feed::getListFeed,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getPostThread(string $uri, ?int $depth = 6, ?int $parentHeight = 80): Response
    {
        return $this->call(
            api: Feed::getPostThread,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getPosts(array $uris): Response
    {
        return $this->call(
            api: Feed::getPosts,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getQuotes(string $uri, ?string $cid = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Feed::getQuotes,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getRepostedBy(string $uri, ?string $cid = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Feed::getRepostedBy,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getSuggestedFeeds(?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Feed::getSuggestedFeeds,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getTimeline(?string $algorithm = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Feed::getTimeline,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function searchPosts(string $q, ?string $sort = 'latest', ?string $since = null, ?string $until = null, ?string $mentions = null, ?string $author = null, ?string $lang = null, ?string $domain = null, ?string $url = null, ?array $tag = null, ?int $limit = 25, ?string $cursor = null): Response
    {
        return $this->call(
            api: Feed::searchPosts,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function sendInteractions(array $interactions): Response
    {
        return $this->call(
            api: Feed::sendInteractions,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
