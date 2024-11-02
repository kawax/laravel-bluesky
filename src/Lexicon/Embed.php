<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon;

enum Embed: string
{
    case External = 'app.bsky.embed.external';
    case Images = 'app.bsky.embed.images';
    case Record = 'app.bsky.embed.record';
    case RecordWithMedia = 'app.bsky.embed.recordWithMedia';
    case Video = 'app.bsky.embed.video';
}
