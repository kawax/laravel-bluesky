<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\SubClient;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Psr\Http\Message\StreamInterface;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Video;
use Revolution\Bluesky\Client\Concerns\AppBskyVideo;
use Revolution\Bluesky\Client\HasHttp;
use Revolution\Bluesky\Contracts\XrpcClient;
use Symfony\Component\Mime\MimeTypes;

class VideoClient implements XrpcClient, Video
{
    use Macroable;
    use Conditionable;
    use HasHttp;
    use AppBskyVideo;

    protected const VIDEO_ENDPOINT = 'https://video.bsky.app/xrpc/';

    public const VIDEO_SERVICE_DID = 'did:web:video.bsky.app';

    /**
     * @param  string  $token  Service Auth token
     */
    public function withServiceAuthToken(string $token): self
    {
        $http = Http::baseUrl(VideoClient::VIDEO_ENDPOINT)
            ->withToken($token);

        return $this->withHttp($http);
    }

    /**
     * {@link AppBskyVideo::uploadVideo()} doesn't work because it is missing required parameters.
     *
     * @link https://github.com/bluesky-social/social-app/blob/main/src/lib/media/video/upload.ts
     */
    public function upload(string $did, StreamInterface|string $data, string $type = 'video/mp4'): Response
    {
        // Generates random names just like the official app
        $name = Str::random(12).$this->ext($type);

        return $this->http()
            ->withBody($data, $type)
            ->post(Video::uploadVideo.'?'.
                http_build_query([
                    'did' => $did,
                    'name' => $name,
                ]),
            );
    }

    protected function ext(string $type): string
    {
        $ext = head(MimeTypes::getDefault()->getExtensions($type));

        if (empty($ext)) {
            return $this->mimeToExt($type);
        }

        return '.'.$ext;
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
