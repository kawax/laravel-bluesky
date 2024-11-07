<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Server;

trait ComAtprotoServer
{
    public function activateAccount(): Response
    {
        return $this->call(
            api: Server::activateAccount,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function checkAccountStatus(): Response
    {
        return $this->call(
            api: Server::checkAccountStatus,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function confirmEmail(string $email, string $token): Response
    {
        return $this->call(
            api: Server::confirmEmail,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function createAccount(string $handle, ?string $email = null, ?string $did = null, ?string $inviteCode = null, ?string $verificationCode = null, ?string $verificationPhone = null, #[\SensitiveParameter] ?string $password = null, ?string $recoveryKey = null, mixed $plcOp = null): Response
    {
        return $this->call(
            api: Server::createAccount,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function createAppPassword(string $name, ?bool $privileged = null): Response
    {
        return $this->call(
            api: Server::createAppPassword,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function createInviteCode(int $useCount, ?string $forAccount = null): Response
    {
        return $this->call(
            api: Server::createInviteCode,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function createInviteCodes(int $codeCount, int $useCount, ?array $forAccounts = null): Response
    {
        return $this->call(
            api: Server::createInviteCodes,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function createSession(string $identifier, #[\SensitiveParameter] string $password, ?string $authFactorToken = null): Response
    {
        return $this->call(
            api: Server::createSession,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function deactivateAccount(?string $deleteAfter = null): Response
    {
        return $this->call(
            api: Server::deactivateAccount,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function deleteAccount(string $did, #[\SensitiveParameter] string $password, string $token): Response
    {
        return $this->call(
            api: Server::deleteAccount,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function deleteSession(): Response
    {
        return $this->call(
            api: Server::deleteSession,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function describeServer(): Response
    {
        return $this->call(
            api: Server::describeServer,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getAccountInviteCodes(?bool $includeUsed = null, ?bool $createAvailable = null): Response
    {
        return $this->call(
            api: Server::getAccountInviteCodes,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getServiceAuth(string $aud, ?int $exp = null, ?string $lxm = null): Response
    {
        return $this->call(
            api: Server::getServiceAuth,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getSession(): Response
    {
        return $this->call(
            api: Server::getSession,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function listAppPasswords(): Response
    {
        return $this->call(
            api: Server::listAppPasswords,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function refreshSession(): Response
    {
        return $this->call(
            api: Server::refreshSession,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function requestAccountDelete(): Response
    {
        return $this->call(
            api: Server::requestAccountDelete,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function requestEmailConfirmation(): Response
    {
        return $this->call(
            api: Server::requestEmailConfirmation,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function requestEmailUpdate(): Response
    {
        return $this->call(
            api: Server::requestEmailUpdate,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function requestPasswordReset(string $email): Response
    {
        return $this->call(
            api: Server::requestPasswordReset,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function reserveSigningKey(?string $did = null): Response
    {
        return $this->call(
            api: Server::reserveSigningKey,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function resetPassword(string $token, #[\SensitiveParameter] string $password): Response
    {
        return $this->call(
            api: Server::resetPassword,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function revokeAppPassword(string $name): Response
    {
        return $this->call(
            api: Server::revokeAppPassword,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function updateEmail(string $email, ?bool $emailAuthFactor = null, ?string $token = null): Response
    {
        return $this->call(
            api: Server::updateEmail,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
