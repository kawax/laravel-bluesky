<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Repo;

trait ComAtprotoRepo
{
    public function applyWrites(string $repo, array $writes, ?bool $validate = null, ?string $swapCommit = null): Response
    {
        return $this->call(
            api: Repo::applyWrites,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function createRecord(string $repo, string $collection, mixed $record, ?string $rkey = null, ?bool $validate = null, ?string $swapCommit = null): Response
    {
        return $this->call(
            api: Repo::createRecord,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function deleteRecord(string $repo, string $collection, string $rkey, ?string $swapRecord = null, ?string $swapCommit = null): Response
    {
        return $this->call(
            api: Repo::deleteRecord,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function describeRepo(string $repo): Response
    {
        return $this->call(
            api: Repo::describeRepo,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getRecord(string $repo, string $collection, string $rkey, ?string $cid = null): Response
    {
        return $this->call(
            api: Repo::getRecord,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function importRepo(): Response
    {
        return $this->call(
            api: Repo::importRepo,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function listMissingBlobs(?int $limit = 500, ?string $cursor = null): Response
    {
        return $this->call(
            api: Repo::listMissingBlobs,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function listRecords(string $repo, string $collection, ?int $limit = 50, ?string $cursor = null, ?string $rkeyStart = null, ?string $rkeyEnd = null, ?bool $reverse = null): Response
    {
        return $this->call(
            api: Repo::listRecords,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function putRecord(string $repo, string $collection, string $rkey, mixed $record, ?bool $validate = null, ?string $swapRecord = null, ?string $swapCommit = null): Response
    {
        return $this->call(
            api: Repo::putRecord,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function uploadBlob(): Response
    {
        return $this->call(
            api: Repo::uploadBlob,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
