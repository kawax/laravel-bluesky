<?php

namespace Revolution\Bluesky\Client\Substitute;

use Revolution\AtProto\Lexicon\Contracts\Chat\Bsky\Actor;
use Revolution\AtProto\Lexicon\Contracts\Chat\Bsky\Convo;
use Revolution\AtProto\Lexicon\Contracts\Chat\Bsky\Moderation;
use Revolution\Bluesky\Client\Concerns\ChatBskyActor;
use Revolution\Bluesky\Client\Concerns\ChatBskyConvo;
use Revolution\Bluesky\Client\Concerns\ChatBskyModeration;
use Revolution\Bluesky\Client\HasHttp;
use Revolution\Bluesky\Contracts\XrpcClient;

class ChatBsky implements XrpcClient, Actor, Convo, Moderation
{
    use HasHttp;

    use ChatBskyActor;
    use ChatBskyConvo;
    use ChatBskyModeration;
}
