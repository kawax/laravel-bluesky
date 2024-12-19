<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

final class DNS
{
    protected static ?string $fake = null;

    /**
     * @return array<array<array-key, string>>
     */
    public static function record(string $hostname, int $type = DNS_TXT): array
    {
        if (! is_null(self::$fake)) {
            return [
                [
                    'txt' => self::$fake,
                ],
            ];
        }

        return dns_get_record($hostname, $type) ?: []; // @codeCoverageIgnore
    }

    /**
     * Set fake TXT.
     */
    public static function fake(?string $txt = null): void
    {
        self::$fake = $txt;
    }
}
