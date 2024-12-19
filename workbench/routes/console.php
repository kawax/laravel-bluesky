<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Revolution\Bluesky\Facades\Bluesky;

/** @var Command $this */
// vendor/bin/testbench bsky:search "#bluesky"
Artisan::command('bsky:search {q=#bluesky}', function () {
    /** @var Command $this */
    /** @var string $q */
    $q = $this->argument('q');
    $response = Bluesky::searchPosts(q: $q, limit: 10);

    $response->collect('posts')
        ->each(function (array $post) {
            /** @var Command $this */
            $this->table(
                ['Name', 'Text'],
                [[data_get($post, 'author.displayName'), data_get($post, 'record.text')]],
            );
            $this->newLine();
        });
});

// vendor/bin/testbench bsky:feed "bsky.app"
Artisan::command('bsky:feed {actor}', function () {
    /** @var Command $this */
    /** @var string $actor */
    $actor = $this->argument('actor');
    $response = Bluesky::getAuthorFeed(actor: $actor, limit: 10);

    $response->collect('feed')
        ->each(function (array $post) {
            /** @var Command $this */
            $this->table(
                ['Name', 'Text'],
                [[data_get($post, 'post.author.displayName'), data_get($post, 'post.record.text')]],
            );
            $this->newLine();
        });
});
