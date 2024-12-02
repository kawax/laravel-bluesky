<?php

namespace Revolution\Bluesky\FeedGenerator;

use BackedEnum;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Revolution\Bluesky\Support\DID;

use function Illuminate\Support\enum_value;

final class FeedGenerator
{
    protected static array $algos;

    protected static ?Closure $validateAuthUsing = null;

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
     * @param  BackedEnum|string  $name  short name. Used in generator url. `at://did:.../app.bsky.feed.generator/{name}`
     * @param  callable(?int $limit, ?string $cursor, ?string $user, Request $request): array  $algo
     */
    public static function register(BackedEnum|string $name, callable $algo): void
    {
        if (! is_callable($algo)) {
            throw new InvalidArgumentException('algo is not callable.');
        }

        self::$algos[enum_value($name)] = $algo(...);
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

    /**
     * ```
     * // AppServiceProvider::boot()
     *
     * use Illuminate\Http\Request;
     * use Revolution\Bluesky\FeedGenerator\FeedGenerator;
     * use Revolution\Bluesky\Socialite\Key\JsonWebToken;
     * use Firebase\JWT\JWT;
     *
     * FeedGenerator::validateAuthUsing(function (?string $jwt, Request $request): ?string {
     *     $jwt = JsonWebToken::explode($jwt);
     *     $header = data_get($jwt, 'header');
     *
     *     $payload = data_get($jwt, 'payload');
     *     $did = data_get($payload, 'iss');
     *
     *     $sig = data_get($jwt, 'sig');
     *     $sig = JWT::urlsafeB64Decode($sig);
     *
     *     // ...
     *
     *     // Returns the user's DID on success, or null on failure.
     *     return $did;
     * });
     * ```
     *
     * @param  null|callable(?string $jwt, Request $request): ?string  $callback
     */
    public static function validateAuthUsing(?callable $callback = null): void
    {
        self::$validateAuthUsing = is_callable($callback) ? $callback(...) : null;
    }

    /**
     * @link https://github.com/bluesky-social/feed-generator/blob/main/src/auth.ts
     */
    public static function validateAuth(Request $request): ?string
    {
        if (is_callable(self::$validateAuthUsing)) {
            return call_user_func(self::$validateAuthUsing, $request->bearerToken(), $request);
        }

        return app()->call(ValidateAuth::class, ['jwt' => $request->bearerToken()]);
    }
}
