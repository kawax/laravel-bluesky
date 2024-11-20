<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\DidDocument;
use Revolution\Bluesky\Support\Identity;
use Symfony\Component\Mime\MimeTypes;

/**
 * Sample command to download the actor's blob files.
 *
 * ```
 * php artisan bluesky:download-blobs ***.bsky.social
 * ```
 *
 * @link https://docs.bsky.app/blog/repo-export
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
    protected $description = 'Download actor\'s blobs. Does not require auth.';

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

        $pds = DidDocument::make()->fetch($did)->pdsUrl();

        $this->line('PDS: '.$pds);

        $response = Bluesky::client(auth: false)
            ->sync()
            ->baseUrl($pds.'/xrpc/')
            ->listBlobs(did: $did)
            ->throw();

        $response->collect('cids')
            ->each(function ($cid) use ($actor, $did, $pds) {
                $content = Bluesky::client(auth: false)
                    ->sync()
                    ->baseUrl($pds.'/xrpc/')
                    ->getBlob(did: $did, cid: $cid)
                    ->throw()
                    ->body();

                $name = Str::slug($actor, dictionary: ['.' => '-', ':' => '-']);
                $file = 'bluesky/download/'.$name.'/blob/'.$cid;
                Storage::put($file, $content);

                $ext = $this->ext(Storage::mimeType($file));
                $file_ext = $file.$ext;

                Storage::move($file, $file_ext);

                $this->info('Download successful: '.Storage::path($file_ext));
            });

        return 0;
    }

    protected function ext($mime): string
    {
        $ext = head(MimeTypes::getDefault()->getExtensions($mime));

        if (empty($ext)) {
            return '';
        }

        return '.'.$ext;
    }
}
