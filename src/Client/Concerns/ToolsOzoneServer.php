<?php

/**
 * GENERATED CODE.
 */

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Server;

trait ToolsOzoneServer
{
    public function getConfig(): Response
    {
        return $this->call(
            api: Server::getConfig,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }
}
