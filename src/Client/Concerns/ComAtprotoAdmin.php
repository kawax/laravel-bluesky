<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Admin;

trait ComAtprotoAdmin
{
    public function deleteAccount(string $did): Response
    {
        return $this->call(
            api: Admin::deleteAccount,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function disableAccountInvites(string $account, ?string $note = null): Response
    {
        return $this->call(
            api: Admin::disableAccountInvites,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function disableInviteCodes(?array $codes = null, ?array $accounts = null): Response
    {
        return $this->call(
            api: Admin::disableInviteCodes,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function enableAccountInvites(string $account, ?string $note = null): Response
    {
        return $this->call(
            api: Admin::enableAccountInvites,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getAccountInfo(string $did): Response
    {
        return $this->call(
            api: Admin::getAccountInfo,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getAccountInfos(array $dids): Response
    {
        return $this->call(
            api: Admin::getAccountInfos,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getInviteCodes(?string $sort = 'recent', ?int $limit = 100, ?string $cursor = null): Response
    {
        return $this->call(
            api: Admin::getInviteCodes,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getSubjectStatus(?string $did = null, ?string $uri = null, ?string $blob = null): Response
    {
        return $this->call(
            api: Admin::getSubjectStatus,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function searchAccounts(?string $email = null, ?string $cursor = null, ?int $limit = 50): Response
    {
        return $this->call(
            api: Admin::searchAccounts,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function sendEmail(string $recipientDid, string $content, string $senderDid, ?string $subject = null, ?string $comment = null): Response
    {
        return $this->call(
            api: Admin::sendEmail,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function updateAccountEmail(string $account, string $email): Response
    {
        return $this->call(
            api: Admin::updateAccountEmail,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function updateAccountHandle(string $did, string $handle): Response
    {
        return $this->call(
            api: Admin::updateAccountHandle,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function updateAccountPassword(string $did, #[\SensitiveParameter] string $password): Response
    {
        return $this->call(
            api: Admin::updateAccountPassword,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function updateSubjectStatus(array $subject, ?array $takedown = null, ?array $deactivated = null): Response
    {
        return $this->call(
            api: Admin::updateSubjectStatus,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
