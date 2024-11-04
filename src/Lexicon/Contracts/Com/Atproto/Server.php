<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\Com\Atproto;

interface Server
{
    /**
     * Activates a currently deactivated account. Used to finalize account migration after the account's repo is imported and identity is setup.
     *
     * method: post
     */
    public function activateAccount();

    /**
     * Returns the status of an account, especially as pertaining to import or recovery. Can be called many times over the course of an account migration. Requires auth and can only be called pertaining to oneself.
     *
     * method: get
     */
    public function checkAccountStatus();

    /**
     * Confirm an email using a token from com.atproto.server.requestEmailConfirmation.
     *
     * method: post
     */
    public function confirmEmail(string $email, string $token);

    /**
     * Create an account. Implemented by PDS.
     *
     * method: post
     */
    public function createAccount(string $handle, ?string $email = null, ?string $did = null, ?string $inviteCode = null, ?string $verificationCode = null, ?string $verificationPhone = null, ?string $password = null, ?string $recoveryKey = null, mixed $plcOp = null);

    /**
     * Create an App Password.
     *
     * method: post
     */
    public function createAppPassword(string $name, ?bool $privileged = null);

    /**
     * Create an invite code.
     *
     * method: post
     */
    public function createInviteCode(int $useCount, ?string $forAccount = null);

    /**
     * Create invite codes.
     *
     * method: post
     */
    public function createInviteCodes(int $codeCount, int $useCount, ?array $forAccounts = null);

    /**
     * Create an authentication session.
     *
     * method: post
     */
    public function createSession(string $identifier, string $password, ?string $authFactorToken = null);

    /**
     * Deactivates a currently active account. Stops serving of repo, and future writes to repo until reactivated. Used to finalize account migration with the old host after the account has been activated on the new host.
     *
     * method: post
     */
    public function deactivateAccount(?string $deleteAfter = null);

    /**
     * Delete an actor's account with a token and password. Can only be called after requesting a deletion token. Requires auth.
     *
     * method: post
     */
    public function deleteAccount(string $did, string $password, string $token);

    /**
     * Delete the current session. Requires auth.
     *
     * method: post
     */
    public function deleteSession();

    /**
     * Describes the server's account creation requirements and capabilities. Implemented by PDS.
     *
     * method: get
     */
    public function describeServer();

    /**
     * Get all invite codes for the current account. Requires auth.
     *
     * method: get
     */
    public function getAccountInviteCodes(?bool $includeUsed = null, ?bool $createAvailable = null);

    /**
     * Get a signed token on behalf of the requesting DID for the requested service.
     *
     * method: get
     */
    public function getServiceAuth(string $aud, ?int $exp = null, ?string $lxm = null);

    /**
     * Get information about the current auth session. Requires auth.
     *
     * method: get
     */
    public function getSession();

    /**
     * List all App Passwords.
     *
     * method: get
     */
    public function listAppPasswords();

    /**
     * Refresh an authentication session. Requires auth using the 'refreshJwt' (not the 'accessJwt').
     *
     * method: post
     */
    public function refreshSession();

    /**
     * Initiate a user account deletion via email.
     *
     * method: post
     */
    public function requestAccountDelete();

    /**
     * Request an email with a code to confirm ownership of email.
     *
     * method: post
     */
    public function requestEmailConfirmation();

    /**
     * Request a token in order to update email.
     *
     * method: post
     */
    public function requestEmailUpdate();

    /**
     * Initiate a user account password reset via email.
     *
     * method: post
     */
    public function requestPasswordReset(string $email);

    /**
     * Reserve a repo signing key, for use with account creation. Necessary so that a DID PLC update operation can be constructed during an account migraiton. Public and does not require auth; implemented by PDS. NOTE: this endpoint may change when full account migration is implemented.
     *
     * method: post
     */
    public function reserveSigningKey(?string $did = null);

    /**
     * Reset a user account password using a token.
     *
     * method: post
     */
    public function resetPassword(string $token, string $password);

    /**
     * Revoke an App Password by name.
     *
     * method: post
     */
    public function revokeAppPassword(string $name);

    /**
     * Update an account's email.
     *
     * method: post
     */
    public function updateEmail(string $email, ?bool $emailAuthFactor = null, ?string $token = null);
}
