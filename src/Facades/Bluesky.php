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
 * @method static Response feed(string $filter = 'posts_with_replies', string $cursor = '')
 * @method static Response timeline(string $cursor = '')
 * @method static Response post(string $text)
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
