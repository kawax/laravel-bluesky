<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon;

enum AtProto: string
{
    /**
     * Delete an actor's account with a token and password. Can only be called after requesting a deletion token. Requires auth.
     */
    case deleteAccount = 'com.atproto.server.deleteAccount';

    /**
     * Disable an account from receiving new invite codes, but does not invalidate existing codes.
     */
    case disableAccountInvites = 'com.atproto.admin.disableAccountInvites';

    /**
     * Disable some set of codes and/or all codes associated with a set of users.
     */
    case disableInviteCodes = 'com.atproto.admin.disableInviteCodes';

    /**
     * Re-enable an account's ability to receive invite codes.
     */
    case enableAccountInvites = 'com.atproto.admin.enableAccountInvites';

    /**
     * Get details about an account.
     */
    case getAccountInfo = 'com.atproto.admin.getAccountInfo';

    /**
     * Get details about some accounts.
     */
    case getAccountInfos = 'com.atproto.admin.getAccountInfos';

    /**
     * Get an admin view of invite codes.
     */
    case getInviteCodes = 'com.atproto.admin.getInviteCodes';

    /**
     * Get the service-specific admin status of a subject (account, record, or blob).
     */
    case getSubjectStatus = 'com.atproto.admin.getSubjectStatus';

    /**
     * Get list of accounts that matches your search query.
     */
    case searchAccounts = 'com.atproto.admin.searchAccounts';

    /**
     * Send email to a user's account email address.
     */
    case sendEmail = 'com.atproto.admin.sendEmail';

    /**
     * Administrative action to update an account's email.
     */
    case updateAccountEmail = 'com.atproto.admin.updateAccountEmail';

    /**
     * Administrative action to update an account's handle.
     */
    case updateAccountHandle = 'com.atproto.admin.updateAccountHandle';

    /**
     * Update the password for a user account as an administrator.
     */
    case updateAccountPassword = 'com.atproto.admin.updateAccountPassword';

    /**
     * Update the service-specific admin status of a subject (account, record, or blob).
     */
    case updateSubjectStatus = 'com.atproto.admin.updateSubjectStatus';

    /**
     * Describe the credentials that should be included in the DID doc of an account that is migrating to this service.
     */
    case getRecommendedDidCredentials = 'com.atproto.identity.getRecommendedDidCredentials';

    /**
     * Request an email with a code to in order to request a signed PLC operation. Requires Auth.
     */
    case requestPlcOperationSignature = 'com.atproto.identity.requestPlcOperationSignature';

    /**
     * Resolves a handle (domain name) to a DID.
     */
    case resolveHandle = 'com.atproto.identity.resolveHandle';

    /**
     * Signs a PLC operation to update some value(s) in the requesting DID's document.
     */
    case signPlcOperation = 'com.atproto.identity.signPlcOperation';

    /**
     * Validates a PLC operation to ensure that it doesn't violate a service's constraints or get the identity into a bad state, then submits it to the PLC registry
     */
    case submitPlcOperation = 'com.atproto.identity.submitPlcOperation';

    /**
     * Updates the current account's handle. Verifies handle validity, and updates did:plc document if necessary. Implemented by PDS, and requires auth.
     */
    case updateHandle = 'com.atproto.identity.updateHandle';

    /**
     * Find labels relevant to the provided AT-URI patterns. Public endpoint for moderation services, though may return different or additional results with auth.
     */
    case queryLabels = 'com.atproto.label.queryLabels';

    /**
     * Submit a moderation report regarding an atproto account or record. Implemented by moderation services (with PDS proxying), and requires auth.
     */
    case createReport = 'com.atproto.moderation.createReport';

    /**
     * Apply a batch transaction of repository creates, updates, and deletes. Requires auth, implemented by PDS.
     */
    case applyWrites = 'com.atproto.repo.applyWrites';

    /**
     * Create a single new repository record. Requires auth, implemented by PDS.
     */
    case createRecord = 'com.atproto.repo.createRecord';

    /**
     * Delete a repository record, or ensure it doesn't exist. Requires auth, implemented by PDS.
     */
    case deleteRecord = 'com.atproto.repo.deleteRecord';

    /**
     * Get information about an account and repository, including the list of collections. Does not require auth.
     */
    case describeRepo = 'com.atproto.repo.describeRepo';

    /**
     * Get a single record from a repository. Does not require auth.
     */
    case getRecord = 'com.atproto.repo.getRecord';

    /**
     * Import a repo in the form of a CAR file. Requires Content-Length HTTP header to be set.
     */
    case importRepo = 'com.atproto.repo.importRepo';

    /**
     * Returns a list of missing blobs for the requesting account. Intended to be used in the account migration flow.
     */
    case listMissingBlobs = 'com.atproto.repo.listMissingBlobs';

    /**
     * List a range of records in a repository, matching a specific collection. Does not require auth.
     */
    case listRecords = 'com.atproto.repo.listRecords';

    /**
     * Write a repository record, creating or updating it as needed. Requires auth, implemented by PDS.
     */
    case putRecord = 'com.atproto.repo.putRecord';

    /**
     * Upload a new blob, to be referenced from a repository record. The blob will be deleted if it is not referenced within a time window (eg, minutes). Blob restrictions (mimetype, size, etc) are enforced when the reference is created. Requires auth, implemented by PDS.
     */
    case uploadBlob = 'com.atproto.repo.uploadBlob';

    /**
     * Activates a currently deactivated account. Used to finalize account migration after the account's repo is imported and identity is setup.
     */
    case activateAccount = 'com.atproto.server.activateAccount';

    /**
     * Returns the status of an account, especially as pertaining to import or recovery. Can be called many times over the course of an account migration. Requires auth and can only be called pertaining to oneself.
     */
    case checkAccountStatus = 'com.atproto.server.checkAccountStatus';

    /**
     * Confirm an email using a token from com.atproto.server.requestEmailConfirmation.
     */
    case confirmEmail = 'com.atproto.server.confirmEmail';

    /**
     * Create an account. Implemented by PDS.
     */
    case createAccount = 'com.atproto.server.createAccount';

    /**
     * Create an App Password.
     */
    case createAppPassword = 'com.atproto.server.createAppPassword';

    /**
     * Create an invite code.
     */
    case createInviteCode = 'com.atproto.server.createInviteCode';

    /**
     * Create invite codes.
     */
    case createInviteCodes = 'com.atproto.server.createInviteCodes';

    /**
     * Create an authentication session.
     */
    case createSession = 'com.atproto.server.createSession';

    /**
     * Deactivates a currently active account. Stops serving of repo, and future writes to repo until reactivated. Used to finalize account migration with the old host after the account has been activated on the new host.
     */
    case deactivateAccount = 'com.atproto.server.deactivateAccount';

    /**
     * Delete the current session. Requires auth.
     */
    case deleteSession = 'com.atproto.server.deleteSession';

    /**
     * Describes the server's account creation requirements and capabilities. Implemented by PDS.
     */
    case describeServer = 'com.atproto.server.describeServer';

    /**
     * Get all invite codes for the current account. Requires auth.
     */
    case getAccountInviteCodes = 'com.atproto.server.getAccountInviteCodes';

    /**
     * Get a signed token on behalf of the requesting DID for the requested service.
     */
    case getServiceAuth = 'com.atproto.server.getServiceAuth';

    /**
     * Get information about the current auth session. Requires auth.
     */
    case getSession = 'com.atproto.server.getSession';

    /**
     * List all App Passwords.
     */
    case listAppPasswords = 'com.atproto.server.listAppPasswords';

    /**
     * Refresh an authentication session. Requires auth using the 'refreshJwt' (not the 'accessJwt').
     */
    case refreshSession = 'com.atproto.server.refreshSession';

    /**
     * Initiate a user account deletion via email.
     */
    case requestAccountDelete = 'com.atproto.server.requestAccountDelete';

    /**
     * Request an email with a code to confirm ownership of email.
     */
    case requestEmailConfirmation = 'com.atproto.server.requestEmailConfirmation';

    /**
     * Request a token in order to update email.
     */
    case requestEmailUpdate = 'com.atproto.server.requestEmailUpdate';

    /**
     * Initiate a user account password reset via email.
     */
    case requestPasswordReset = 'com.atproto.server.requestPasswordReset';

    /**
     * Reserve a repo signing key, for use with account creation. Necessary so that a DID PLC update operation can be constructed during an account migraiton. Public and does not require auth; implemented by PDS. NOTE: this endpoint may change when full account migration is implemented.
     */
    case reserveSigningKey = 'com.atproto.server.reserveSigningKey';

    /**
     * Reset a user account password using a token.
     */
    case resetPassword = 'com.atproto.server.resetPassword';

    /**
     * Revoke an App Password by name.
     */
    case revokeAppPassword = 'com.atproto.server.revokeAppPassword';

    /**
     * Update an account's email.
     */
    case updateEmail = 'com.atproto.server.updateEmail';

    /**
     * Check accounts location in signup queue.
     */
    case checkSignupQueue = 'com.atproto.temp.checkSignupQueue';

    /**
     * DEPRECATED: use queryLabels or subscribeLabels instead -- Fetch all labels from a labeler created after a certain date.
     */
    case fetchLabels = 'com.atproto.temp.fetchLabels';

    /**
     * Request a verification code to be sent to the supplied phone number
     */
    case requestPhoneVerification = 'com.atproto.temp.requestPhoneVerification';
}
