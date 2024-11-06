<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Graph;
use Revolution\Bluesky\Client\HasHttp;

trait AppBskyGraph
{
    use HasHttp;

    public function getActorStarterPacks(string $actor, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Graph::getActorStarterPacks,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getBlocks(?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Graph::getBlocks,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getFollowers(string $actor, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Graph::getFollowers,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getFollows(string $actor, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Graph::getFollows,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getKnownFollowers(string $actor, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Graph::getKnownFollowers,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getList(string $list, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Graph::getList,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getListBlocks(?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Graph::getListBlocks,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getListMutes(?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Graph::getListMutes,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getLists(string $actor, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Graph::getLists,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getMutes(?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Graph::getMutes,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getRelationships(string $actor, ?array $others = null): Response
    {
        return $this->call(
            api: Graph::getRelationships,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getStarterPack(string $starterPack): Response
    {
        return $this->call(
            api: Graph::getStarterPack,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getStarterPacks(array $uris): Response
    {
        return $this->call(
            api: Graph::getStarterPacks,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getSuggestedFollowsByActor(string $actor): Response
    {
        return $this->call(
            api: Graph::getSuggestedFollowsByActor,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function muteActor(string $actor): Response
    {
        return $this->call(
            api: Graph::muteActor,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function muteActorList(string $list): Response
    {
        return $this->call(
            api: Graph::muteActorList,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function muteThread(string $root): Response
    {
        return $this->call(
            api: Graph::muteThread,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function unmuteActor(string $actor): Response
    {
        return $this->call(
            api: Graph::unmuteActor,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function unmuteActorList(string $list): Response
    {
        return $this->call(
            api: Graph::unmuteActorList,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function unmuteThread(string $root): Response
    {
        return $this->call(
            api: Graph::unmuteThread,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
