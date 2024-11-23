<?php
/**
 * GENERATED CODE.
 */

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Sync;

trait ComAtprotoSync
{
    public function getBlob(string $did, string $cid): Response
    {
        return $this->call(
            api: Sync::getBlob,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getBlocks(string $did, array $cids): Response
    {
        return $this->call(
            api: Sync::getBlocks,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getCheckout(string $did): Response
    {
        return $this->call(
            api: Sync::getCheckout,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getHead(string $did): Response
    {
        return $this->call(
            api: Sync::getHead,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getLatestCommit(string $did): Response
    {
        return $this->call(
            api: Sync::getLatestCommit,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getRecord(string $did, string $collection, string $rkey, ?string $commit = null): Response
    {
        return $this->call(
            api: Sync::getRecord,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getRepo(string $did, ?string $since = null): Response
    {
        return $this->call(
            api: Sync::getRepo,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getRepoStatus(string $did): Response
    {
        return $this->call(
            api: Sync::getRepoStatus,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function listBlobs(string $did, ?string $since = null, ?int $limit = 500, ?string $cursor = null): Response
    {
        return $this->call(
            api: Sync::listBlobs,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function listRepos(?int $limit = 500, ?string $cursor = null): Response
    {
        return $this->call(
            api: Sync::listRepos,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function notifyOfUpdate(string $hostname): Response
    {
        return $this->call(
            api: Sync::notifyOfUpdate,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function requestCrawl(string $hostname): Response
    {
        return $this->call(
            api: Sync::requestCrawl,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
