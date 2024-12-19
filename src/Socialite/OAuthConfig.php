<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socialite;

use Closure;
use Illuminate\Support\Facades\Route;
use Revolution\Bluesky\Socialite\Key\JsonWebKeySet;

class OAuthConfig
{
    protected static ?Closure $metadataUsing = null;

    protected static ?Closure $jwksUsing = null;

    /**
     * Overrides the `client-metadata.json` response.
     *
     * ```
     * // AppServiceProvider::boot()
     *
     * OAuthConfig::clientMetadataUsing(function() {
     *     return [];
     * });
     * ```
     */
    public static function clientMetadataUsing(?callable $callback): void
    {
        static::$metadataUsing = is_callable($callback) ? $callback(...) : null;
    }

    public static function clientMetadata(): mixed
    {
        if (is_callable(static::$metadataUsing)) {
            return call_user_func(static::$metadataUsing);
        }

        if (Route::has('bluesky.oauth.redirect')) {
            $redirect = route('bluesky.oauth.redirect');
        } else {
            $redirect = 'http://127.0.0.1:8000/';
        }

        return collect((array) config('bluesky.oauth.metadata'))
            ->merge(
                [
                    'client_id' => route('bluesky.oauth.client-metadata'),
                    'jwks_uri' => route('bluesky.oauth.jwks'),
                    'redirect_uris' => [url(config('bluesky.oauth.redirect') ?? $redirect)],
                ],
            )->reject(fn ($item) => is_null($item))
            ->toArray();
    }

    /**
     * Overrides the `jwks.json` response.
     *
     * ```
     * // AppServiceProvider::boot()
     *
     * OAuthConfig::jwksUsing(function() {
     *     return [];
     * });
     * ```
     */
    public static function jwksUsing(?callable $callback): void
    {
        static::$jwksUsing = is_callable($callback) ? $callback(...) : null;
    }

    public static function jwks(): mixed
    {
        if (is_callable(static::$jwksUsing)) {
            return call_user_func(static::$jwksUsing);
        }

        return JsonWebKeySet::load()->toArray();
    }
}
