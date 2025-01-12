<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Revolution\Bluesky\FeedGenerator\FeedGenerator;
use Revolution\Bluesky\Labeler\Labeler;
use Workbench\App\FeedGenerator\ArtisanFeed;
use Workbench\App\Labeler\ArtisanLabeler;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        FeedGenerator::register('artisan', ArtisanFeed::class);

        Labeler::register(ArtisanLabeler::class);
    }
}
