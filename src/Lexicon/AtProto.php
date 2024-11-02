<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon;

enum AtProto: string
{
    case deleteAccount = 'com.atproto.server.deleteAccount';
    case disableAccountInvites = 'com.atproto.admin.disableAccountInvites';
    case disableInviteCodes = 'com.atproto.admin.disableInviteCodes';
    case enableAccountInvites = 'com.atproto.admin.enableAccountInvites';
    case getAccountInfo = 'com.atproto.admin.getAccountInfo';
    case getAccountInfos = 'com.atproto.admin.getAccountInfos';
    case getInviteCodes = 'com.atproto.admin.getInviteCodes';
    case getSubjectStatus = 'com.atproto.admin.getSubjectStatus';
    case searchAccounts = 'com.atproto.admin.searchAccounts';
    case sendEmail = 'com.atproto.admin.sendEmail';
    case updateAccountEmail = 'com.atproto.admin.updateAccountEmail';
    case updateAccountHandle = 'com.atproto.admin.updateAccountHandle';
    case updateAccountPassword = 'com.atproto.admin.updateAccountPassword';
    case updateSubjectStatus = 'com.atproto.admin.updateSubjectStatus';
    case getRecommendedDidCredentials = 'com.atproto.identity.getRecommendedDidCredentials';
    case requestPlcOperationSignature = 'com.atproto.identity.requestPlcOperationSignature';
    case resolveHandle = 'com.atproto.identity.resolveHandle';
    case signPlcOperation = 'com.atproto.identity.signPlcOperation';
    case submitPlcOperation = 'com.atproto.identity.submitPlcOperation';
    case updateHandle = 'com.atproto.identity.updateHandle';
    case queryLabels = 'com.atproto.label.queryLabels';
    case createReport = 'com.atproto.moderation.createReport';
    case applyWrites = 'com.atproto.repo.applyWrites';
    case createRecord = 'com.atproto.repo.createRecord';
    case deleteRecord = 'com.atproto.repo.deleteRecord';
    case describeRepo = 'com.atproto.repo.describeRepo';
    case getRecord = 'com.atproto.repo.getRecord';
    case importRepo = 'com.atproto.repo.importRepo';
    case listMissingBlobs = 'com.atproto.repo.listMissingBlobs';
    case listRecords = 'com.atproto.repo.listRecords';
    case putRecord = 'com.atproto.repo.putRecord';
    case uploadBlob = 'com.atproto.repo.uploadBlob';
    case activateAccount = 'com.atproto.server.activateAccount';
    case checkAccountStatus = 'com.atproto.server.checkAccountStatus';
    case confirmEmail = 'com.atproto.server.confirmEmail';
    case createAccount = 'com.atproto.server.createAccount';
    case createAppPassword = 'com.atproto.server.createAppPassword';
    case createInviteCode = 'com.atproto.server.createInviteCode';
    case createInviteCodes = 'com.atproto.server.createInviteCodes';
    case createSession = 'com.atproto.server.createSession';
    case deactivateAccount = 'com.atproto.server.deactivateAccount';
    case deleteSession = 'com.atproto.server.deleteSession';
    case describeServer = 'com.atproto.server.describeServer';
    case getAccountInviteCodes = 'com.atproto.server.getAccountInviteCodes';
    case getServiceAuth = 'com.atproto.server.getServiceAuth';
    case getSession = 'com.atproto.server.getSession';
    case listAppPasswords = 'com.atproto.server.listAppPasswords';
    case refreshSession = 'com.atproto.server.refreshSession';
    case requestAccountDelete = 'com.atproto.server.requestAccountDelete';
    case requestEmailConfirmation = 'com.atproto.server.requestEmailConfirmation';
    case requestEmailUpdate = 'com.atproto.server.requestEmailUpdate';
    case requestPasswordReset = 'com.atproto.server.requestPasswordReset';
    case reserveSigningKey = 'com.atproto.server.reserveSigningKey';
    case resetPassword = 'com.atproto.server.resetPassword';
    case revokeAppPassword = 'com.atproto.server.revokeAppPassword';
    case updateEmail = 'com.atproto.server.updateEmail';
    case checkSignupQueue = 'com.atproto.temp.checkSignupQueue';
    case fetchLabels = 'com.atproto.temp.fetchLabels';
    case requestPhoneVerification = 'com.atproto.temp.requestPhoneVerification';
}
