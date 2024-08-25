<?php

declare(strict_types=1);

namespace Revolution\Bluesky;

use Illuminate\Support\ServiceProvider;
use Revolution\Bluesky\Contracts\Factory;

class BlueskyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Factory::class, fn () => new BlueskyClient(config('bluesky.service', 'https://bsky.social')));

        if (! $this->app->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__.'/../config/bluesky.php', 'bluesky');
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/bluesky.php' => config_path('bluesky.php'),
            ], 'bluesky-config');
        }
    }
}
