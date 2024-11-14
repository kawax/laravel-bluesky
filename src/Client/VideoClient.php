<?php

namespace Revolution\Bluesky\Client;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Video;
use Revolution\Bluesky\Client\Concerns\AppBskyVideo;

class VideoClient implements Video
{
    use Macroable;
    use Conditionable;
    use HasHttp;
    use AppBskyVideo;

    /**
     * uploadVideo() doesn't work because it is missing required parameters.
     */
    public function upload(#[Format('did')] string $did, mixed $data, string $name, string $type = 'video/mp4'): Response
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
