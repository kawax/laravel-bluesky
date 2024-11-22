<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

class DNS
{
    protected static ?string $fake = null;

    public static function record(string $hostname, int $type = DNS_TXT): array
    {
        if (! is_null(static::$fake)) {
            return [
                [
                    'txt' => static::$fake,
                ],
            ];
        }

        return dns_get_record($hostname, $type) ?: [];// @codeCoverageIgnore
    }

    /**
     * Set fake TXT.
     */
    public static function fake(?string $txt = null): void
    {
        static::$fake = $txt;
    }
}
