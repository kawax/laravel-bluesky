<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Feed;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Label;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Moderation;
use Revolution\Bluesky\BlueskyManager;
use Revolution\Bluesky\Console\DownloadBlobsCommand;
use Revolution\Bluesky\Console\DownloadRecordCommand;
use Revolution\Bluesky\Console\DownloadRepoCommand;
use Revolution\Bluesky\Console\Labeler\LabelerDeclareLabelsCommand;
use Revolution\Bluesky\Console\Labeler\LabelerNewPrivateKeyCommand;
use Revolution\Bluesky\Console\Labeler\LabelerPollingCommand;
use Revolution\Bluesky\Console\Labeler\LabelerServeCommand;
use Revolution\Bluesky\Console\Labeler\LabelerSetupCommand;
use Revolution\Bluesky\Console\LexiconClientCommand;
use Revolution\Bluesky\Console\NewPrivateKeyCommand;
use Revolution\Bluesky\Console\UnpackRepoCommand;
use Revolution\Bluesky\Console\WebSocket\FirehoseServeCommand;
use Revolution\Bluesky\Console\WebSocket\JetstreamServeCommand;
use Revolution\Bluesky\Contracts\Factory;
use Revolution\Bluesky\FeedGenerator\Http\DescribeFeedController;
use Revolution\Bluesky\FeedGenerator\Http\FeedSkeletonController;
use Revolution\Bluesky\Labeler\Http\LabelerController;
use Revolution\Bluesky\Socialite\BlueskyProvider;
use Revolution\Bluesky\Socialite\Http\OAuthMetaController;
use Revolution\Bluesky\WellKnown\Http\WellKnownController;

class BlueskyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/bluesky.php', 'bluesky');

        $this->app->scoped(Factory::class, BlueskyManager::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/bluesky.php' => config_path('bluesky.php'),
            ], 'bluesky-config');

            $this->commands([
                NewPrivateKeyCommand::class,
                JetstreamServeCommand::class,
                DownloadRepoCommand::class,
                UnpackRepoCommand::class,
                DownloadBlobsCommand::class,
                DownloadRecordCommand::class,
                FirehoseServeCommand::class,
                LabelerNewPrivateKeyCommand::class,
                LabelerSetupCommand::class,
                LabelerPollingCommand::class,
                LabelerDeclareLabelsCommand::class,
                LabelerServeCommand::class,
            ]);

            if (class_exists(LexiconClientCommand::class)) {
                $this->commands([
                    LexiconClientCommand::class,
                ]);
            }
        }

        $this->socialite();
        $this->generator();
        $this->well();
        $this->labeler();
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

    protected function generator(): void
    {
        if (config('bluesky.generator.disabled')) {
            return;
        }

        Route::prefix('/xrpc/')
            ->group(function () {
                Route::get(Feed::getFeedSkeleton, FeedSkeletonController::class)
                    ->name('bluesky.feed.skeleton');
                Route::get(Feed::describeFeedGenerator, DescribeFeedController::class)
                    ->name('bluesky.feed.describe');
            });
    }

    protected function well(): void
    {
        if (config('bluesky.well-known.disabled')) {
            return;
        }

        Route::get('.well-known/did.json', [WellKnownController::class, 'did'])
            ->name('bluesky.well-known.did');
        Route::get('.well-known/atproto-did', [WellKnownController::class, 'atproto'])
            ->name('bluesky.well-known.atproto');
    }

    protected function labeler(): void
    {
        if (Config::boolean('bluesky.labeler.disabled')) {
            return;
        }

        Route::prefix('/xrpc/')
            ->group(function () {
                Route::get(Label::queryLabels, [LabelerController::class, 'queryLabels'])
                    ->name('bluesky.labeler.query');
                Route::post(Moderation::createReport, [LabelerController::class, 'createReport'])
                    ->name('bluesky.labeler.report');
            });
    }
}
