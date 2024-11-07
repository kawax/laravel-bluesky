<?php

namespace Revolution\Bluesky\Client\Substitute;

use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Sync;
use Revolution\Bluesky\Client\Concerns\ComAtprotoSync;
use Revolution\Bluesky\Client\HasHttp;
use Revolution\Bluesky\Contracts\XrpcClient;

class AtprotoSync implements XrpcClient, Sync
{
    use HasHttp;

    use ComAtprotoSync;
}
