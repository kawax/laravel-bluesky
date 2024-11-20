<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Sync;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\DidDocument;
use Revolution\Bluesky\Support\Identity;

/**
 * Sample command to download the actor's "CAR" file. Does not include parsing the Car file.
 *
 * ```
 * php artisan bluesky:download-repo ***.bsky.social
 * ```
 *
 * @link https://docs.bsky.app/blog/repo-export
 */
class DownloadRepoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:download-repo {actor : DID or handle}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download actor\'s car file. Does not require auth.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $actor = $this->argument('actor');

        $this->line('Actor: '.$actor);

        if (Identity::isHandle($actor)) {
            $did = Bluesky::resolveHandle($actor)->json('did');
        } else {
            $did = $actor;
        }

        if (! Identity::isDID($did)) {
            $this->error('Invalid actor');
            return 1;
        }

        $this->line('DID: '.$did);

        $didDoc = DidDocument::make()->fetch($did);

        $pds = $didDoc->pdsUrl();

        $this->line('PDS: '.$pds);

        $response = Bluesky::client(auth: false)
            ->sync()
            ->baseUrl($pds.'/xrpc/')
            ->getRepo(did: $did)
            ->throw();

        if ($response->successful()) {
            $name = Str::slug($actor, dictionary: ['.' => '-', ':' => '-']);
            $file = 'bluesky/download/'.$name.'/'.$name.'.car';
            Storage::put($file, $response->body());

            $this->info('Download successful: '.Storage::path($file));
        }

        return 0;
    }
}
