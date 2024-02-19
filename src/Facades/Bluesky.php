<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Revolution\Bluesky\Contracts\Factory;

/**
 * @method static static service(string $service)
 * @method static \Closure|null session(string $key)
 * @method static static login(string $identifier, string $password)
 * @method static Collection feed(string $filter = 'posts_with_replies')
 * @method static Collection timeline(string $cursor = '')
 * @method static Collection post(string $text)
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
