<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Labeler\DeleteLabelDefinitions;
use Revolution\Bluesky\Labeler\DeclareLabelDefinitions;
use Revolution\Bluesky\Labeler\SetupLabeler;
use Revolution\Bluesky\Support\Identity;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

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

// vendor/bin/testbench bsky:labeler-declare
Artisan::command('bsky:labeler-declare', function () {
    dump((new DeclareLabelDefinitions())());
});

// vendor/bin/testbench bsky:labeler-delete
Artisan::command('bsky:labeler-delete', function () {
    dump((new DeleteLabelDefinitions())());
});

// vendor/bin/testbench bsky:labeler-setup
Artisan::command('bsky:labeler-setup', function () {

    $handle = text(
        label: 'Enter DID or handle',
        default: Config::string('bluesky.labeler.identifier'),
        required: true,
        hint: 'DID or handle of the account to use'
    );

    if (! Identity::isDID($handle)) {
        $did = Bluesky::resolveHandle($handle)['did'];
    } else {
        $did = $handle;
    }

    /** @var string $did */
    if (! Identity::isDID($did)) {
        throw new RuntimeException("Could not resolve '$handle' to a valid account. Please try again.");
    }

    dump($did);

    $password = password(
        label: 'Enter Account Password',
        required: true,
        hint: 'Account password (cannot be an app password)'
    );

    $service = text(
        label: 'Enter PDS URL',
        default: Bluesky::entryway(),
        required: true,
        hint: 'URL of the PDS where the account is located'
    );

    $res = Bluesky::login($did, $password, $service)
        ->client()
        ->requestPlcOperationSignature();

    if ($res->failed()) {
        dump($res->json());

        return 1;
    }

    $plcToken = password(
        label: 'Enter PLC Token',
        required: true,
        hint: 'You will receive a confirmation code via email.'
    );

    $endpoint = text(
        label: 'Enter Endpoint URL',
        default: Str::rtrim(url('/'), '/'),
        required: true,
        hint: 'URL where the labeler will be hosted:'
    );

    dump((new SetupLabeler())($did, $password, $service, $plcToken, $endpoint));

    $this->info('If successful, PLC will be updated: https://plc.directory/'.$did);
});
