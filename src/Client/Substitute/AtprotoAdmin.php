<?php

namespace Revolution\Bluesky\Client\Substitute;

use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Admin;
use Revolution\Bluesky\Client\Concerns\ComAtprotoAdmin;
use Revolution\Bluesky\Client\HasHttp;
use Revolution\Bluesky\Contracts\XrpcClient;

class AtprotoAdmin implements XrpcClient, Admin
{
    use HasHttp;

    use ComAtprotoAdmin;
}
