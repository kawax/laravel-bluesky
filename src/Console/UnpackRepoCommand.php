<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Revolution\Bluesky\Support\CAR;
use Revolution\Bluesky\Support\CBOR;

/**
 * Sample command to unpack the actor's "CAR" file.
 *
 * ```
 * php artisan bluesky:download-repo ***.bsky.social
 *
 * php artisan bluesky:unpack-repo ***.bsky.social
 * ```
 *
 * @link https://docs.bsky.app/blog/repo-export
 */
class UnpackRepoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:unpack-repo {actor : DID or handle}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unpack actor\'s car file.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $actor = $this->argument('actor');

        $this->warn('Actor: '.$actor);

        $name = Str::slug($actor, dictionary: ['.' => '-', ':' => '-']);

        $file = collect(['bluesky', 'download', $name, $name.'.car'])
            ->implode(DIRECTORY_SEPARATOR);

        foreach (CAR::blockIterator(Utils::streamFor(Storage::readStream($file))) as $cid => $block) {
            //dump($cid, $block);

            if (Arr::exists($block, '$type')) {
                $collection = data_get($block, '$type');
            } elseif (Arr::exists($block, 'e')) {
                $collection = '_mst';
            } else {
                continue;
            }

            // TODO
            $block = CBOR::normalize($block);

            $path = collect(['bluesky', 'download', $name, 'repo', $collection, $cid.'.json'])
                ->implode(DIRECTORY_SEPARATOR);

            Storage::put($path, json_encode($block, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $this->line('Unpack: '.Storage::path($path));
        }

        $this->info('Unpack successful');

        return 0;
    }
}
