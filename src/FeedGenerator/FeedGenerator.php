<?php

namespace Revolution\Bluesky\FeedGenerator;

use Illuminate\Http\Request;

final class FeedGenerator
{
    protected static array $algos;

    /**
     * Register FeedGenerator algorithm.
     *
     * ```
     * // Register in your AppServiceProvider::boot()
     *
     * use use Revolution\Bluesky\FeedGenerator\FeedGenerator;
     *
     * FeedGenerator::register(name: 'artisan', algo: function(?int $limit, ?string $cursor): array {
     *     // The implementation is entirely up to you.
     *
     *     $response = Bluesky::searchPosts(q: '#laravel', limit: $limit, cursor: $cursor);
     *
     *     $cursor = $response->json('cursor');
     *     $feed = $response->collect('posts')->map(function(array $post) {
     *         return ['post' => data_get($post, 'uri')];
     *     })->toArray();
     *
     *     return compact('cursor', 'feed');
     * });
     * ```
     *
     * @param  string  $name  short name. Used in generator url. `at://did:.../app.bsky.feed.generator/{name}`
     * @param  callable(int $limit, string $cursor, string $feed): array  $algo
     */
    public static function register(string $name, callable $algo): void
    {
        self::$algos[$name] = $algo;
    }

    public static function getFeedSkeleton(string $name, ?int $limit, ?string $cursor, Request $request): mixed
    {
        return call_user_func(self::$algos[$name], $limit, $cursor, $request);
    }

    public static function has(string $name): bool
    {
        return isset(self::$algos[$name]);
    }

    public static function missing(string $name): bool
    {
        return ! self::has($name);
    }
}
