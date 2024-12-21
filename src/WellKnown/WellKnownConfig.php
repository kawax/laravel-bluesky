<?php

declare(strict_types=1);

namespace Revolution\Bluesky\WellKnown;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Revolution\Bluesky\Support\DID;

class WellKnownConfig
{
    protected static ?Closure $didUsing = null;

    protected static ?Closure $atprotoDidUsing = null;

    /**
     * Overrides the `/.well-known/did.json` response.
     *
     * ```
     * // AppServiceProvider::boot()
     *
     * WellKnownConfig::didUsing(function () {
     *     return [];
     * });
     * ```
     */
    public static function didUsing(?callable $callback): void
    {
        static::$didUsing = is_callable($callback) ? $callback(...) : null;
    }

    /**
     * Default is a setting for FeedGenerator.
     */
    public static function did(): mixed
    {
        if (is_callable(static::$didUsing)) {
            return call_user_func(static::$didUsing);
        }

        $endpoint = Str::rtrim(url('/'), '/');

        $service = [];

        if (! Config::boolean('bluesky.generator.disabled')) {
            $service[] = [
                'id' => '#bsky_fg',
                'type' => 'BskyFeedGenerator',
                'serviceEndpoint' => $endpoint,
            ];
        }

        return [
            '@context' => [
                'https://www.w3.org/ns/did/v1',
            ],
            'id' => config('bluesky.generator.service') ?? DID::web(),
            'service' => $service,
        ];
    }

    /**
     * Overrides the `/.well-known/atproto-did` response.
     *
     * ```
     * // AppServiceProvider::boot()
     *
     * WellKnownConfig::atprotoDidUsing(fn() => 'did:plc:***');
     * ```
     */
    public static function atprotoDidUsing(?callable $callback): void
    {
        static::$atprotoDidUsing = is_callable($callback) ? $callback(...) : null;
    }

    /**
     * Default is empty, since it can also be set by DNS.
     */
    public static function atprotoDid(): mixed
    {
        if (is_callable(static::$atprotoDidUsing)) {
            return call_user_func(static::$atprotoDidUsing);
        }

        return '';
    }
}
