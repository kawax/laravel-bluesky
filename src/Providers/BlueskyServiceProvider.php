<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use Revolution\Bluesky\BlueskyClient;
use Revolution\Bluesky\Console\LexiconContractsCommand;
use Revolution\Bluesky\Console\LexiconEnumCommand;
use Revolution\Bluesky\Console\NewPrivateKeyCommand;
use Revolution\Bluesky\Contracts\Factory;
use Revolution\Bluesky\Socalite\BlueskyProvider;
use Revolution\Bluesky\Socalite\Http\OAuthMetaController;

class BlueskyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/bluesky.php', 'bluesky');

        $this->app->scoped(Factory::class, BlueskyClient::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/bluesky.php' => config_path('bluesky.php'),
            ], 'bluesky-config');

            $this->commands([
                NewPrivateKeyCommand::class,
            ]);

            if (class_exists(LexiconEnumCommand::class)) {
                $this->commands([
                    LexiconEnumCommand::class,
                    LexiconContractsCommand::class,
                ]);
            }
        }

        $this->socialite();
    }

    protected function socialite(): void
    {
        if (config('bluesky.oauth.disabled')) {
            return;
        }

        Socialite::extend('bluesky', function ($app) {
            if ($app->isProduction()) {
                $client_id = route('bluesky.oauth.client-metadata');
            } else {
                $client_id = 'http://localhost';
            }

            if ($app->isProduction() && Route::has('bluesky.oauth.redirect')) {
                $redirect = route('bluesky.oauth.redirect');
            } else {
                $redirect = 'http://127.0.0.1:8000/';
            }

            return Socialite::buildProvider(BlueskyProvider::class, [
                'client_id' => url(config('bluesky.oauth.client_id') ?? $client_id),
                'client_secret' => '',
                'redirect' => url(config('bluesky.oauth.redirect') ?? $redirect),
            ]);
        });

        Route::prefix(config('bluesky.oauth.prefix') ?? '/bluesky/oauth/')
            ->group(function () {
                Route::get('client-metadata.json', [OAuthMetaController::class, 'clientMetadata'])
                    ->name('bluesky.oauth.client-metadata');
                Route::get('jwks.json', [OAuthMetaController::class, 'jwks'])
                    ->name('bluesky.oauth.jwks');
            });
    }
}
