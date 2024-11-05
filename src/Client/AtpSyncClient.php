<?php

namespace Revolution\Bluesky\Client;

use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Sync;

class AtpSyncClient implements Sync
{
    use HasHttp;

    public function getBlob(string $did, string $cid)
    {
        // TODO: Implement getBlob() method.
    }

    public function getBlocks(string $did, array $cids)
    {
        // TODO: Implement getBlocks() method.
    }

    public function getCheckout(string $did)
    {
        // TODO: Implement getCheckout() method.
    }

    public function getHead(string $did)
    {
        // TODO: Implement getHead() method.
    }

    public function getLatestCommit(string $did)
    {
        // TODO: Implement getLatestCommit() method.
    }

    public function getRecord(string $did, string $collection, string $rkey, ?string $commit = null)
    {
        // TODO: Implement getRecord() method.
    }

    public function getRepo(string $did, ?string $since = null)
    {
        // TODO: Implement getRepo() method.
    }

    public function getRepoStatus(string $did)
    {
        // TODO: Implement getRepoStatus() method.
    }

    public function listBlobs(string $did, ?string $since = null, ?int $limit = 500, ?string $cursor = null)
    {
        // TODO: Implement listBlobs() method.
    }

    public function listRepos(?int $limit = 500, ?string $cursor = null)
    {
        // TODO: Implement listRepos() method.
    }

    public function notifyOfUpdate(string $hostname)
    {
        // TODO: Implement notifyOfUpdate() method.
    }

    public function requestCrawl(string $hostname)
    {
        // TODO: Implement requestCrawl() method.
    }
}
