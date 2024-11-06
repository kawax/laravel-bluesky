<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Temp;
use Revolution\Bluesky\Client\HasHttp;

trait ComAtprotoTemp
{
    use HasHttp;

    public function checkSignupQueue(): Response
    {
        return $this->call(
            api: Temp::checkSignupQueue,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function fetchLabels(?int $since = null, ?int $limit = 50): Response
    {
        return $this->call(
            api: Temp::fetchLabels,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function requestPhoneVerification(string $phoneNumber): Response
    {
        return $this->call(
            api: Temp::requestPhoneVerification,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}