<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Revolution\Bluesky\Facades\Bluesky;

/** @var \Illuminate\Console\Command $this */
Artisan::command('inspire', function () {
    /** @var \Illuminate\Console\Command $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// vendor/bin/testbench bsky:search "#bluesky"
Artisan::command('bsky:search {q=#bluesky}', function () {
    /** @var \Illuminate\Console\Command $this */
    $response = Bluesky::searchPosts(q: $this->argument('q'), limit: 10);

    $response->collect('posts')
        ->each(function (array $post) {
            /** @var \Illuminate\Console\Command $this */
            $this->table(
                ['Name', 'Text'],
                [[data_get($post, 'author.displayName'), data_get($post, 'record.text')]],
            );
            $this->newLine();
        });
});

// vendor/bin/testbench bsky:feed "bsky.app"
Artisan::command('bsky:feed {actor}', function () {
    /** @var \Illuminate\Console\Command $this */
    $response = Bluesky::getAuthorFeed(actor: $this->argument('actor'), limit: 10);

    $response->collect('feed')
        ->each(function (array $post) {
            /** @var \Illuminate\Console\Command $this */
            $this->table(
                ['Name', 'Text'],
                [[data_get($post, 'post.author.displayName'), data_get($post, 'post.record.text')]],
            );
            $this->newLine();
        });
});
