<?php

namespace Revolution\Bluesky\Client;

use Illuminate\Http\Client\Response;
use Revolution\Bluesky\Lexicon\Contracts\Com\Atproto\Identity;
use Revolution\Bluesky\Lexicon\Contracts\Com\Atproto\Repo;
use Revolution\Bluesky\Lexicon\Enum\AtProto;

class AtpClient implements Repo, Identity
{
    use HasHttp;

    public function applyWrites(string $repo, array $writes, ?bool $validate = null, ?string $swapCommit = null)
    {
        // TODO: Implement applyWrites() method.
    }

    public function createRecord(string $repo, string $collection, $record, ?string $rkey = null, ?bool $validate = null, ?string $swapCommit = null): Response
    {
        return $this->call(
            api: AtProto::createRecord,
            method: self::post,
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

    public function getRecommendedDidCredentials()
    {
        // TODO: Implement getRecommendedDidCredentials() method.
    }

    public function requestPlcOperationSignature()
    {
        // TODO: Implement requestPlcOperationSignature() method.
    }

    public function resolveHandle(string $handle): Response
    {
        return $this->call(
            api: AtProto::resolveHandle,
            method: self::get,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function signPlcOperation(?string $token = null, ?array $rotationKeys = null, ?array $alsoKnownAs = null, $verificationMethods = null, $services = null)
    {
        // TODO: Implement signPlcOperation() method.
    }

    public function submitPlcOperation($operation)
    {
        // TODO: Implement submitPlcOperation() method.
    }

    public function updateHandle(string $handle)
    {
        // TODO: Implement updateHandle() method.
    }
}
