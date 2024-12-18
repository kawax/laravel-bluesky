<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Revolution\Bluesky\Core\CAR;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\DidDocument;
use Revolution\Bluesky\Support\Identity;

/**
 * Sample command to download the actor's "CAR" file.
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

        $this->warn('Actor: '.$actor);

        if (Identity::isHandle($actor)) {
            $did = Bluesky::resolveHandle($actor)->json('did');
        } else {
            $did = $actor;
        }

        if (! Identity::isDID($did)) {
            $this->error('Invalid actor');

            return 1;
        }

        $this->warn('DID: '.$did);

        $didDoc = DidDocument::make(Bluesky::identity()->resolveDID($did, cache: false)->json());
        $pds = $didDoc->pdsUrl();

        $this->warn('PDS: '.$pds);

        $response = Bluesky::client(auth: false)
            ->sync()
            ->baseUrl($pds.'/xrpc/')
            ->getRepo(did: $did)
            ->throw();

        $name = Str::slug($actor, dictionary: ['.' => '-', ':' => '-']);

        $dic_file = collect(['bluesky', 'download', $name, $name.'-did.json'])
            ->implode(DIRECTORY_SEPARATOR);
        Storage::put($dic_file, json_encode($didDoc->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $file = collect(['bluesky', 'download', $name, $name.'.car'])
            ->implode(DIRECTORY_SEPARATOR);

        Storage::put($file, $response->body());

        $this->line('Download: '.Storage::path($file));

        [$roots, $blocks] = CAR::decode(Utils::streamFor(Storage::readStream($file)));

        if (Arr::exists($blocks, $roots[0])) {
            $signed_commit = data_get($blocks, $roots[0]);
            dump($signed_commit);
        }

        $this->info('Download successful');

        return 0;
    }
}
