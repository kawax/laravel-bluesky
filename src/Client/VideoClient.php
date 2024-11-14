<?php

namespace Revolution\Bluesky\Client;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Video;

class VideoClient
{
    use Macroable;
    use Conditionable;

    use HasHttp;

    public function uploadVideo(#[Format('did')] string $did, mixed $data, string $name, string $type = 'video/mp4'): Response
    {
        return $this->http()
            ->withBody($data, $type)
            ->withUrlParameters([
                'did' => $did,
                'name' => $name,
            ])
            ->post(Video::uploadVideo);
    }
}
