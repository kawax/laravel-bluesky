<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon;

enum Embed: string
{
    /**
     * A representation of some externally linked content (eg, a URL and 'card'), embedded in a Bluesky record (eg, a post).
     */
    case External = 'app.bsky.embed.external';

    /**
     * A set of images embedded in a Bluesky record (eg, a post).
     */
    case Images = 'app.bsky.embed.images';

    /**
     * A representation of a record embedded in a Bluesky record (eg, a post). For example, a quote-post, or sharing a feed generator record.
     */
    case Record = 'app.bsky.embed.record';

    /**
     * A representation of a record embedded in a Bluesky record (eg, a post), alongside other compatible embeds. For example, a quote post and image, or a quote post and external URL card.
     */
    case RecordWithMedia = 'app.bsky.embed.recordWithMedia';

    /**
     * A video embedded in a Bluesky record (eg, a post).
     */
    case Video = 'app.bsky.embed.video';
}
