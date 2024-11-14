<?php

namespace Revolution\Bluesky\Client;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Video;
use Revolution\Bluesky\Client\Concerns\AppBskyVideo;

class VideoClient
{
    use Macroable;
    use Conditionable;
    use HasHttp;

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

    public function uploadVideo(#[Format('did')] string $did, mixed $data, string $name, string $type = 'video/mp4'): Response
    {
        return $this->http()
            ->withBody($data, $type)
            ->post(Video::uploadVideo.'?'.http_build_query([
                    'did' => $did,
                    'name' => $name,
                ]),
            );
    }
}
