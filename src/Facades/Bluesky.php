<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Facades;

use BackedEnum;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Facade;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Contracts\Factory;
use Revolution\Bluesky\Contracts\XrpcClient;
use Revolution\Bluesky\Notifications\BlueskyMessage;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Support\Identity;
use Revolution\Bluesky\Support\PDS;

/**
 * @method static Agent agent()
 * @method static static withToken(OAuthSession $token)
 * @method static static withAgent(?Agent $agent)
 * @method static PendingRequest http(bool $auth = true)
 * @method static static login(string $identifier, string $password)
 * @method static Response send(BackedEnum|string $api, string $method = 'get', bool $auth = true, ?array $params = null)
 * @method static XrpcClient client(bool $auth = false)
 * @method static Response resolveHandle(string $handle)
 * @method static Response profile(?string $actor = null)
 * @method static static logout()
 * @method static bool check()
 * @method static Response feed(?string $actor = null, int $limit = 50, string $cursor = '', string $filter = 'posts_with_replies')
 * @method static Response timeline(int $limit = 50, string $cursor = '')
 * @method static Response createRecord(string $repo, string $collection, array $record)
 * @method static Response post(string|BlueskyMessage $text)
 * @method static Response uploadBlob(mixed $data, string $type = 'image/png')
 * @method static static refreshSession()
 * @method static Identity identity()
 * @method static PDS pds()
 * @method static string entryway()
 * @method static void macro(string $name, object|callable $macro)
 * @method static static|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static static|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
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
