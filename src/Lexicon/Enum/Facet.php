<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Enum;

enum Facet: string
{
    /**
     * Facet feature for mention of another account. The text is usually a handle, including a '@' prefix, but the facet reference is a DID.
     */
    case Mention = 'app.bsky.richtext.facet#mention';

    /**
     * Facet feature for a URL. The text URL may have been simplified or truncated, but the facet reference should be a complete URL.
     */
    case Link = 'app.bsky.richtext.facet#link';

    /**
     * Facet feature for a hashtag. The text usually includes a '#' prefix, but the facet reference should not (except in the case of 'double hash tags').
     */
    case Tag = 'app.bsky.richtext.facet#tag';
}
