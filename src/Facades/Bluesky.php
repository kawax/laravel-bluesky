<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Facades;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Facade;
use Revolution\Bluesky\Contracts\Factory;

/**
 * @method static static service(string $service)
 * @method static mixed session(string $key)
 * @method static static login(string $identifier, string $password)
 * @method static static logout()
 * @method static bool check()
 * @method static Response feed(int $limit = 50, string $cursor = '', string $filter = 'posts_with_replies')
 * @method static Response timeline(int $limit = 50, string $cursor = '')
 * @method static Response post(string $text)
 * @method static void macro(string $name, object|callable $macro)
 * @method static \Revolution\Bluesky\BlueskyClient|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Revolution\Bluesky\BlueskyClient|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 *
 * @see \Revolution\Bluesky\BlueskyClient
 */
class Bluesky extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return Factory::class;
    }
}
