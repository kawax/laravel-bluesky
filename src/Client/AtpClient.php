<?php

namespace Revolution\Bluesky\Client;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Identity;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Repo;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Server;

class AtpClient implements Repo, Identity, Server
{
    use HasHttp;

    public function applyWrites(string $repo, array $writes, ?bool $validate = null, ?string $swapCommit = null)
    {
        // TODO: Implement applyWrites() method.
    }

    public function createRecord(string $repo, string $collection, mixed $record, ?string $rkey = null, ?bool $validate = null, ?string $swapCommit = null): Response
    {
        return $this->call(
            api: self::createRecord,
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

    public function putRecord(string $repo, string $collection, string $rkey, mixed $record, ?bool $validate = null, ?string $swapRecord = null, ?string $swapCommit = null)
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
            api: self::resolveHandle,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function signPlcOperation(?string $token = null, ?array $rotationKeys = null, ?array $alsoKnownAs = null, mixed $verificationMethods = null, mixed $services = null)
    {
        // TODO: Implement signPlcOperation() method.
    }

    public function submitPlcOperation(mixed $operation)
    {
        // TODO: Implement submitPlcOperation() method.
    }

    public function updateHandle(string $handle)
    {
        // TODO: Implement updateHandle() method.
    }

    public function activateAccount()
    {
        // TODO: Implement activateAccount() method.
    }

    public function checkAccountStatus()
    {
        // TODO: Implement checkAccountStatus() method.
    }

    public function confirmEmail(string $email, string $token)
    {
        // TODO: Implement confirmEmail() method.
    }

    public function createAccount(string $handle, ?string $email = null, ?string $did = null, ?string $inviteCode = null, ?string $verificationCode = null, ?string $verificationPhone = null, ?string $password = null, ?string $recoveryKey = null, mixed $plcOp = null)
    {
        // TODO: Implement createAccount() method.
    }

    public function createAppPassword(string $name, ?bool $privileged = null)
    {
        // TODO: Implement createAppPassword() method.
    }

    public function createInviteCode(int $useCount, ?string $forAccount = null)
    {
        // TODO: Implement createInviteCode() method.
    }

    public function createInviteCodes(int $codeCount, int $useCount, ?array $forAccounts = null)
    {
        // TODO: Implement createInviteCodes() method.
    }

    public function createSession(string $identifier, string $password, ?string $authFactorToken = null): Response
    {
        return $this->call(
            api: self::createSession,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function deactivateAccount(?string $deleteAfter = null)
    {
        // TODO: Implement deactivateAccount() method.
    }

    public function deleteAccount(string $did, string $password, string $token)
    {
        // TODO: Implement deleteAccount() method.
    }

    public function deleteSession()
    {
        // TODO: Implement deleteSession() method.
    }

    public function describeServer()
    {
        // TODO: Implement describeServer() method.
    }

    public function getAccountInviteCodes(?bool $includeUsed = null, ?bool $createAvailable = null)
    {
        // TODO: Implement getAccountInviteCodes() method.
    }

    public function getServiceAuth(string $aud, ?int $exp = null, ?string $lxm = null)
    {
        // TODO: Implement getServiceAuth() method.
    }

    public function getSession()
    {
        // TODO: Implement getSession() method.
    }

    public function listAppPasswords()
    {
        // TODO: Implement listAppPasswords() method.
    }

    public function refreshSession()
    {
        // TODO: Implement refreshSession() method.
    }

    public function requestAccountDelete()
    {
        // TODO: Implement requestAccountDelete() method.
    }

    public function requestEmailConfirmation()
    {
        // TODO: Implement requestEmailConfirmation() method.
    }

    public function requestEmailUpdate()
    {
        // TODO: Implement requestEmailUpdate() method.
    }

    public function requestPasswordReset(string $email)
    {
        // TODO: Implement requestPasswordReset() method.
    }

    public function reserveSigningKey(?string $did = null)
    {
        // TODO: Implement reserveSigningKey() method.
    }

    public function resetPassword(string $token, string $password)
    {
        // TODO: Implement resetPassword() method.
    }

    public function revokeAppPassword(string $name)
    {
        // TODO: Implement revokeAppPassword() method.
    }

    public function updateEmail(string $email, ?bool $emailAuthFactor = null, ?string $token = null)
    {
        // TODO: Implement updateEmail() method.
    }
}
