<?php

namespace Revolution\Bluesky\Support;

/**
 * @codeCoverageIgnore
 */
class DNS
{
    public function record(string $hostname, int $type = DNS_TXT): array
    {
        return dns_get_record($hostname, $type) ?: [];
    }
}
