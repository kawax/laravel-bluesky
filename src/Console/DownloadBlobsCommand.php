<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\DidDocument;
use Revolution\Bluesky\Support\Identity;

/**
 * Sample command to download the actor's blob files.
 *
 * ```
 * php artisan bluesky:download-blobs ***.bsky.social
 * ```
 *
 * @see https://docs.bsky.app/blog/repo-export
 */
class DownloadBlobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:download-blobs {actor : DID or handle}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download actor\'s blobs';

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
            ->listBlobs(did: $did)
            ->throw();

        $response->collect('cids')
            ->each(function ($cid) use ($actor, $did, $pds) {
                $response = Bluesky::client(auth: false)
                    ->sync()
                    ->baseUrl($pds.'/xrpc/')
                    ->getBlob(did: $did, cid: $cid)
                    ->throw();

                $content = $response->body();

                $type = getimagesizefromstring($content);
                $ext = '';
                if (is_array($type)) {
                    $ext = image_type_to_extension($type[2]);
                    if ($ext === false) {
                        $ext = '';
                    }
                }

                $name = Str::slug($actor, dictionary: ['.' => '-', ':' => '-']);
                $file = 'bluesky/download/'.$name.'/_blob/'.$cid.$ext;
                Storage::put($file, $content);

                $this->info('Download successful: '.Storage::path($file));
            });

        return 0;
    }
}
