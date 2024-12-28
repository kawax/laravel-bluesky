<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Facades;

use BackedEnum;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Facade;
use Revolution\Bluesky\Client\AtpClient;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Contracts\Factory;
use Revolution\Bluesky\Session\AbstractSession;
use Revolution\Bluesky\Support\Identity;
use Revolution\Bluesky\Support\PDS;

/**
 * @method static static withToken(?AbstractSession $token)
 * @method static AtpClient client(bool $auth = true)
 * @method static Response send(BackedEnum|string $api, string $method = 'get', bool $auth = true, ?array $params = null, ?callable $callback = null)
 * @method static Agent agent()
 * @method static static withAgent(?Agent $agent)
 * @method static static login(string $identifier, string $password, ?string $service = null)
 * @method static static logout()
 * @method static bool check()
 * @method static Identity identity()
 * @method static PDS pds()
 * @method static string entryway()
 *
 * @mixin \Revolution\Bluesky\BlueskyManager
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
