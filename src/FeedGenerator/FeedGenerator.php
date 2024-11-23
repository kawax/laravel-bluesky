<?php

namespace Revolution\Bluesky\FeedGenerator;

use Illuminate\Http\Request;
use Revolution\Bluesky\Support\DID;

final class FeedGenerator
{
    protected static array $algos;

    /**
     * Register FeedGenerator algorithm.
     *
     * ```
     * // Register in your AppServiceProvider::boot()
     *
     * use Illuminate\Http\Request;
     * use Revolution\Bluesky\FeedGenerator\FeedGenerator;
     *
     * FeedGenerator::register(name: 'artisan', algo: function (?int $limit, ?string $cursor, ?string $user, Request $request): array {
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
     * @param  callable(?int $limit, ?string $cursor, ?string $user, Request $request): array  $algo
     */
    public static function register(string $name, callable $algo): void
    {
        self::$algos[$name] = $algo;
    }

    public static function getFeedSkeleton(string $name, ?int $limit, ?string $cursor, ?string $user, Request $request): mixed
    {
        return call_user_func(self::$algos[$name], $limit, $cursor, $user, $request);
    }

    public static function describeFeedGenerator(): array
    {
        $pub = config('bluesky.generator.publisher') ?? DID::web();

        $feeds = collect(self::$algos)
            ->keys()
            ->map(fn ($algo) => 'at://'.$pub.'/app.bsky.feed.generator/'.$algo)
            ->toArray();

        return [
            'did' => config('bluesky.generator.service') ?? DID::web(),
            'feeds' => $feeds,
        ];
    }

    public static function has(string $name): bool
    {
        return isset(self::$algos[$name]);
    }

    public static function missing(string $name): bool
    {
        return ! self::has($name);
    }

    /**
     * Remove all algos.
     */
    public static function flush(): void
    {
        self::$algos = [];
    }
}
