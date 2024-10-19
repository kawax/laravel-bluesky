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
        //
    }
}
