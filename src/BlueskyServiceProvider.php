<?php

declare(strict_types=1);

namespace Revolution\Bluesky;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use Revolution\Bluesky\Console\NewPrivateKeyCommand;
use Revolution\Bluesky\Contracts\Factory;
use Revolution\Bluesky\Socalite\BlueskyProvider;
use Revolution\Bluesky\Socalite\Http\OAuthMetaController;

class BlueskyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bluesky.php', 'bluesky');

        $this->app->scoped(Factory::class, BlueskyClient::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/bluesky.php' => config_path('bluesky.php'),
            ], 'bluesky-config');

            $this->commands([
                NewPrivateKeyCommand::class,
            ]);
        }

        $this->socialite();
    }

    protected function socialite(): void
    {
        if (config('bluesky.oauth.disabled')) {
            return;
        }

        Socialite::extend('bluesky', function ($app) {
            return Socialite::buildProvider(BlueskyProvider::class, [
                'client_id' => Route::has('bluesky.oauth.client-metadata') ? route('bluesky.oauth.client-metadata') : '',
                'client_secret' => '',
                'redirect' => Route::has('bluesky.oauth.redirect') ? route('bluesky.oauth.redirect') : url('bluesky/callback'),
            ]);
        });

        Route::prefix(config('bluesky.oauth.prefix', '/bluesky/oauth/'))
            ->group(function () {
                Route::get('client-metadata.json', [OAuthMetaController::class, 'clientMetadata'])
                    ->name('bluesky.oauth.client-metadata');
                Route::get('jwks.json', [OAuthMetaController::class, 'jwks'])
                    ->name('bluesky.oauth.jwks');
            });
    }
}
