<?php

namespace Revolution\Bluesky\Socalite;

use Closure;

class OAuthConfig
{
    protected static ?Closure $metadataUsing = null;

    protected static ?Closure $jwksUsing = null;

    public static function clientMetadataUsing(?Closure $callback): void
    {
        static::$metadataUsing = $callback;
    }

    public static function clientMetadata(): mixed
    {
        if (is_callable(static::$metadataUsing)) {
            return call_user_func(static::$metadataUsing);
        }

        return collect(config('bluesky.oauth.metadata'))
            ->merge(
                [
                    'client_id' => route('bluesky.oauth.client-metadata'),
                    'jwks_uri' => route('bluesky.oauth.jwks'),
                    'redirect_uris' => [route('bluesky.oauth.redirect')],
                ],
            )->reject(fn ($item) => is_null($item))->toArray();
    }

    public static function jwksUsing(?Closure $callback): void
    {
        static::$jwksUsing = $callback;
    }

    public static function jwks(): mixed
    {
        if (is_callable(static::$jwksUsing)) {
            return call_user_func(static::$jwksUsing);
        }

        return JsonWebKeySet::load();
    }
}
