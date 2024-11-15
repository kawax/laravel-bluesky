<?php

namespace Revolution\Bluesky\Client;

use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Contracts\Chat\Bsky\Actor;
use Revolution\AtProto\Lexicon\Contracts\Chat\Bsky\Convo;
use Revolution\AtProto\Lexicon\Contracts\Chat\Bsky\Moderation;
use Revolution\Bluesky\Client\Concerns\ChatBskyActor;
use Revolution\Bluesky\Client\Concerns\ChatBskyConvo;
use Revolution\Bluesky\Client\Concerns\ChatBskyModeration;

/**
 * Chat / DM Client.
 *
 * chat.bsky
 */
class ChatClient implements Actor, Convo, Moderation
{
    use Macroable;
    use Conditionable;
    use HasHttp;

    use ChatBskyActor;
    use ChatBskyConvo;
    use ChatBskyModeration;
}
