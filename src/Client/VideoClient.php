<?php

namespace Revolution\Bluesky\Client;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Psr\Http\Message\StreamInterface;
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
     * {@link AppBskyVideo::uploadVideo()} doesn't work because it is missing required parameters.
     */
    public function upload(#[Format('did')] string $did, StreamInterface|string $data, string $type = 'video/mp4'): Response
    {
        $name = Str::random(12).$this->mimeToExt($type);

        return $this->http()
            ->withBody($data, $type)
            ->post(Video::uploadVideo.'?'.http_build_query([
                    'did' => $did,
                    'name' => $name,
                ]),
            );
    }

    protected function mimeToExt(string $type): string
    {
        return match ($type) {
            'video/mp4' => '.mp4',
            'video/webm' => '.webm',
            'video/mpeg' => '.mpeg',
            'video/quicktime' => '.mov',
            'image/gif' => '.gif',
            default => '',
        };
    }
}
