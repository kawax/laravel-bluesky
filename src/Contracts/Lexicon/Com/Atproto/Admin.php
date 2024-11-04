<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Contracts\Lexicon\Com\Atproto;

interface Admin
{
    /**
     * Delete a user account as an administrator.
     *
     * method: post
     */
    public function deleteAccount(string $did);

    /**
     * Disable an account from receiving new invite codes, but does not invalidate existing codes.
     *
     * method: post
     */
    public function disableAccountInvites(string $account, ?string $note = null);

    /**
     * Disable some set of codes and/or all codes associated with a set of users.
     *
     * method: post
     */
    public function disableInviteCodes(?array $codes = null, ?array $accounts = null);

    /**
     * Re-enable an account's ability to receive invite codes.
     *
     * method: post
     */
    public function enableAccountInvites(string $account, ?string $note = null);

    /**
     * Get details about an account.
     *
     * method: get
     */
    public function getAccountInfo(string $did);

    /**
     * Get details about some accounts.
     *
     * method: get
     */
    public function getAccountInfos(array $dids);

    /**
     * Get an admin view of invite codes.
     *
     * method: get
     */
    public function getInviteCodes(?string $sort = 'recent', ?int $limit = 100, ?string $cursor = null);

    /**
     * Get the service-specific admin status of a subject (account, record, or blob).
     *
     * method: get
     */
    public function getSubjectStatus(?string $did = null, ?string $uri = null, ?string $blob = null);

    /**
     * Get list of accounts that matches your search query.
     *
     * method: get
     */
    public function searchAccounts(?string $email = null, ?string $cursor = null, ?int $limit = 50);

    /**
     * Send email to a user's account email address.
     *
     * method: post
     */
    public function sendEmail(string $recipientDid, string $content, string $senderDid, ?string $subject = null, ?string $comment = null);

    /**
     * Administrative action to update an account's email.
     *
     * method: post
     */
    public function updateAccountEmail(string $account, string $email);

    /**
     * Administrative action to update an account's handle.
     *
     * method: post
     */
    public function updateAccountHandle(string $did, string $handle);

    /**
     * Update the password for a user account as an administrator.
     *
     * method: post
     */
    public function updateAccountPassword(string $did, string $password);

    /**
     * Update the service-specific admin status of a subject (account, record, or blob).
     *
     * method: post
     */
    public function updateSubjectStatus(array $subject, ?array $takedown = null, ?array $deactivated = null);
}
