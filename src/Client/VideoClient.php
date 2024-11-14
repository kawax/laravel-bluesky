<?php

namespace Revolution\Bluesky\Client;

use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Video;
use Revolution\Bluesky\Client\Concerns\AppBskyVideo;

class VideoClient implements Video
{
    use Macroable;
    use Conditionable;

    use HasHttp;

    use AppBskyVideo;
}
