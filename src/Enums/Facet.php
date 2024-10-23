<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Enums;

enum Facet: string
{
    case Mention = 'app.bsky.richtext.facet#mention';
    case Link = 'app.bsky.richtext.facet#link';
    case Tag = 'app.bsky.richtext.facet#tag';
}
