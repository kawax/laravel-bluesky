<?php

namespace Revolution\Bluesky\Client;

use Illuminate\Http\Client\Response;
use Revolution\Bluesky\Lexicon\Contracts\Com\Atproto\Repo;
use Revolution\Bluesky\Lexicon\Enum\AtProto;

class AtpClient extends AbstractClient implements Repo
{
    public function applyWrites(string $repo, array $writes, ?bool $validate = null, ?string $swapCommit = null)
    {
        // TODO: Implement applyWrites() method.
    }

    public function createRecord(string $repo, string $collection, $record, ?string $rkey = null, ?bool $validate = null, ?string $swapCommit = null): Response
    {
        return $this->call(
            api: AtProto::createRecord,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function deleteRecord(string $repo, string $collection, string $rkey, ?string $swapRecord = null, ?string $swapCommit = null)
    {
        // TODO: Implement deleteRecord() method.
    }

    public function describeRepo(string $repo)
    {
        // TODO: Implement describeRepo() method.
    }

    public function getRecord(string $repo, string $collection, string $rkey, ?string $cid = null)
    {
        // TODO: Implement getRecord() method.
    }

    public function importRepo()
    {
        // TODO: Implement importRepo() method.
    }

    public function listMissingBlobs(?int $limit = 500, ?string $cursor = null)
    {
        // TODO: Implement listMissingBlobs() method.
    }

    public function listRecords(string $repo, string $collection, ?int $limit = 50, ?string $cursor = null, ?string $rkeyStart = null, ?string $rkeyEnd = null, ?bool $reverse = null)
    {
        // TODO: Implement listRecords() method.
    }

    public function putRecord(string $repo, string $collection, string $rkey, $record, ?bool $validate = null, ?string $swapRecord = null, ?string $swapCommit = null)
    {
        // TODO: Implement putRecord() method.
    }

    public function uploadBlob()
    {
        // TODO: Implement uploadBlob() method.
    }
}
