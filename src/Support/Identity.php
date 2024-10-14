<?php

namespace Revolution\Bluesky\Support;

class Identity
{
    protected const HANDLE_REGEX = '/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/';

    protected const DID_REGEX = '/^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$/';

    public static function isHandle(string $handle): bool
    {
        return preg_match(self::HANDLE_REGEX, $handle) === 1;
    }

    public static function isDID(string $did): bool
    {
        return preg_match(self::DID_REGEX, $did) === 1;
    }
}
