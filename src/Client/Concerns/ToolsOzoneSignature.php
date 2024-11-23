<?php
/**
 * GENERATED CODE.
 */

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Signature;

trait ToolsOzoneSignature
{
    public function findCorrelation(array $dids): Response
    {
        return $this->call(
            api: Signature::findCorrelation,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function findRelatedAccounts(string $did, ?string $cursor = null, ?int $limit = 50): Response
    {
        return $this->call(
            api: Signature::findRelatedAccounts,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function searchAccounts(array $values, ?string $cursor = null, ?int $limit = 50): Response
    {
        return $this->call(
            api: Signature::searchAccounts,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }
}
