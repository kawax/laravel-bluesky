<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Revolution\Bluesky\Core\CAR;
use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CID;

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

        if (! Storage::exists($file)) {
            $this->error('File not found: '.$file);

            return 1;
        }

        $stream = Utils::streamFor(Storage::readStream($file));

        // Unpack only record data for all collections
        foreach (CAR::blockMap($stream) as $key => $record) {
            [$collection, $rkey] = explode('/', $key);
            $block = data_get($record, 'value');
            $cid = data_get($record, 'cid');

            if (CID::verify(CBOR::encode($block), $cid)) {
                $this->info('Verified');
            } else {
                $this->error('Verify failed');
            }

            $path = collect(['bluesky', 'download', $name, 'repo', $collection, $rkey.'.json'])
                ->implode(DIRECTORY_SEPARATOR);

            Storage::put($path, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $this->line('Unpack: '.Storage::path($path));
        }

        // $this->blockIterator($name, $file);

        $this->info('Unpack successful');

        return 0;
    }

    /**
     * A different version using CAR::blockIterator().
     * Use this if you need _mst or _commit.
     *
     * @phpstan-ignore method.unused
     */
    private function blockIterator(string $name, string $file): void
    {
        foreach (CAR::blockIterator(Utils::streamFor(Storage::readStream($file))) as $cid => $block) {
            //dump($cid, $block);

            if (Arr::exists($block, '$type')) {
                $collection = data_get($block, '$type');
            } elseif (Arr::exists($block, 'e')) {
                $collection = '_mst';
            } elseif (Arr::exists($block, 'sig')) {
                $collection = '_commit';
            } else {
                dump($block);
                continue;
            }

            if (Arr::exists($block, '$type')) {
                if (CID::verify(CBOR::encode($block), $cid)) {
                    $this->info('Verified');
                } else {
                    $this->error('Verify failed');
                }
            }

            $path = collect(['bluesky', 'download', $name, 'repo', $collection, $cid.'.json'])
                ->implode(DIRECTORY_SEPARATOR);

            Storage::put($path, json_encode($block, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $this->line('Unpack: '.Storage::path($path));
        }
    }
}
