<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\Com\Atproto;

interface Identity
{
    public const getRecommendedDidCredentials = 'com.atproto.identity.getRecommendedDidCredentials';
    public const requestPlcOperationSignature = 'com.atproto.identity.requestPlcOperationSignature';
    public const resolveHandle = 'com.atproto.identity.resolveHandle';
    public const signPlcOperation = 'com.atproto.identity.signPlcOperation';
    public const submitPlcOperation = 'com.atproto.identity.submitPlcOperation';
    public const updateHandle = 'com.atproto.identity.updateHandle';

    /**
     * Describe the credentials that should be included in the DID doc of an account that is migrating to this service.
     *
     * method: get
     */
    public function getRecommendedDidCredentials();

    /**
     * Request an email with a code to in order to request a signed PLC operation. Requires Auth.
     *
     * method: post
     */
    public function requestPlcOperationSignature();

    /**
     * Resolves a handle (domain name) to a DID.
     *
     * method: get
     */
    public function resolveHandle(string $handle);

    /**
     * Signs a PLC operation to update some value(s) in the requesting DID's document.
     *
     * method: post
     */
    public function signPlcOperation(?string $token = null, ?array $rotationKeys = null, ?array $alsoKnownAs = null, mixed $verificationMethods = null, mixed $services = null);

    /**
     * Validates a PLC operation to ensure that it doesn't violate a service's constraints or get the identity into a bad state, then submits it to the PLC registry
     *
     * method: post
     */
    public function submitPlcOperation(mixed $operation);

    /**
     * Updates the current account's handle. Verifies handle validity, and updates did:plc document if necessary. Implemented by PDS, and requires auth.
     *
     * method: post
     */
    public function updateHandle(string $handle);
}
