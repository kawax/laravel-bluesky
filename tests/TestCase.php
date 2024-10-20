<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Application;
use Laravel\Socialite\SocialiteServiceProvider;
use Revolution\Bluesky\BlueskyServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Load package service provider.
     *
     * @param  Application  $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            BlueskyServiceProvider::class,
            SocialiteServiceProvider::class,
        ];
    }

    /**
     * Load package alias.
     *
     * @param  Application  $app
     * @return array
     */
    protected function getPackageAliases($app): array
    {
        return [
            //
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('bluesky.oauth', [
            //for testing
            'private_key' => 'LS0tLS1CRUdJTiBQUklWQVRFIEtFWS0tLS0tDQpNSUdIQWdFQU1CTUdCeXFHU000OUFnRUdDQ3FHU000OUF3RUhCRzB3YXdJQkFRUWdEZkhxckZzSkRUUkVMa1ZIDQpHNG9DNTh2U2k0SnFkN3NPSTVNdTZzeVdrY21oUkFOQ0FBVHc2VU1DMlpYcFV4blhFc1BjRTA5aFdoYWdLbWxODQpRUXovSUlFYUVHdEFtSU5YeGUzTHZ0NE5KUS9YVWdGV3hkdEJBbUhQcFN4MlM3RnIvdmFhT2UzZw0KLS0tLS1FTkQgUFJJVkFURSBLRVktLS0tLQ',
        ]);
    }
}
