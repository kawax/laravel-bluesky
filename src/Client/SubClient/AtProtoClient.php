<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\SubClient;

use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Identity;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Label;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Moderation;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Repo;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Server;
use Revolution\Bluesky\Client\Concerns\ComAtprotoIdentity;
use Revolution\Bluesky\Client\Concerns\ComAtprotoLabel;
use Revolution\Bluesky\Client\Concerns\ComAtprotoModeration;
use Revolution\Bluesky\Client\Concerns\ComAtprotoRepo;
use Revolution\Bluesky\Client\Concerns\ComAtprotoServer;
use Revolution\Bluesky\Client\HasHttp;
use Revolution\Bluesky\Contracts\XrpcClient;

class AtProtoClient implements
    XrpcClient,
    Identity,
    Label,
    Moderation,
    Repo,
    Server
{
    use Macroable;
    use Conditionable;

    use HasHttp;

    use ComAtprotoIdentity;
    use ComAtprotoLabel;
    use ComAtprotoModeration;
    use ComAtprotoRepo;
    use ComAtprotoServer;
}
