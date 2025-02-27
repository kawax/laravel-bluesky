<?php
/**
 * GENERATED CODE.
 */

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Identity;

trait ComAtprotoIdentity
{
    public function getRecommendedDidCredentials(): Response
    {
        return $this->call(
            api: Identity::getRecommendedDidCredentials,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function refreshIdentity(string $identifier): Response
    {
        return $this->call(
            api: Identity::refreshIdentity,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function requestPlcOperationSignature(): Response
    {
        return $this->call(
            api: Identity::requestPlcOperationSignature,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function resolveDid(string $did): Response
    {
        return $this->call(
            api: Identity::resolveDid,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function resolveHandle(string $handle): Response
    {
        return $this->call(
            api: Identity::resolveHandle,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function resolveIdentity(string $identifier): Response
    {
        return $this->call(
            api: Identity::resolveIdentity,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function signPlcOperation(?string $token = null, ?array $rotationKeys = null, ?array $alsoKnownAs = null, mixed $verificationMethods = null, mixed $services = null): Response
    {
        return $this->call(
            api: Identity::signPlcOperation,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function submitPlcOperation(mixed $operation): Response
    {
        return $this->call(
            api: Identity::submitPlcOperation,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function updateHandle(string $handle): Response
    {
        return $this->call(
            api: Identity::updateHandle,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
