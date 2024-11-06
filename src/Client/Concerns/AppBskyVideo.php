<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Video;

trait AppBskyVideo
{
    public function getJobStatus(string $jobId): Response
    {
        return $this->call(
            api: Video::getJobStatus,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getUploadLimits(): Response
    {
        return $this->call(
            api: Video::getUploadLimits,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function uploadVideo(): Response
    {
        return $this->call(
            api: Video::uploadVideo,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
