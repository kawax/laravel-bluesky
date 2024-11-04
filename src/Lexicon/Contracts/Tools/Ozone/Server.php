<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\Tools\Ozone;

interface Server
{
    public const getConfig = 'tools.ozone.server.getConfig';

    /**
     * Get details about ozone's server configuration.
     *
     * method: get
     */
    public function getConfig();
}
