<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\SubClient;

use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Communication;
use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Moderation;
use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Server;
use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Set;
use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Setting;
use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Signature;
use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Team;
use Revolution\Bluesky\Client\Concerns\ToolsOzoneCommunication;
use Revolution\Bluesky\Client\Concerns\ToolsOzoneModeration;
use Revolution\Bluesky\Client\Concerns\ToolsOzoneServer;
use Revolution\Bluesky\Client\Concerns\ToolsOzoneSet;
use Revolution\Bluesky\Client\Concerns\ToolsOzoneSetting;
use Revolution\Bluesky\Client\Concerns\ToolsOzoneSignature;
use Revolution\Bluesky\Client\Concerns\ToolsOzoneTeam;
use Revolution\Bluesky\Client\HasHttp;
use Revolution\Bluesky\Contracts\XrpcClient;

class OzoneClient implements XrpcClient, Communication, Moderation, Server, Set, Setting, Signature, Team
{
    use HasHttp;

    use ToolsOzoneCommunication;
    use ToolsOzoneModeration;
    use ToolsOzoneServer;
    use ToolsOzoneSet;
    use ToolsOzoneSetting;
    use ToolsOzoneSignature;
    use ToolsOzoneTeam;
}
