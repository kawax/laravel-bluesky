<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console\WebSocket;

use Illuminate\Console\Command;
use Revolution\Bluesky\WebSocket\JetstreamServer;
use Workerman\Worker;

/**
 * ```
 * // No filters. Receive all messages.
 * php artisan bluesky:ws start
 * ```
 * ```
 * // Filter to a specific collection type. To specify multiple collections, use multiple "-C".
 * php artisan bluesky:ws start -C app.bsky.feed.post -C app.bsky.feed.like -C ...
 * ```
 * ```
 * // To filter by user, specify DID
 * php artisan bluesky:ws start -C app.bsky.feed.post -D did:plc:... -D did:...
 * ```
 * ```
 * // Specify the host
 * php artisan bluesky:ws start -H jetstream2.us-east.bsky.network
 * ```
 * ```
 * // Set maxMessageSizeBytes
 * php artisan bluesky:ws start -M 1000000
 * ```
 * ```
 * // Display all received messages for debugging.
 * php artisan bluesky:ws start -v
 * ```
 *
 * This is an advanced usage so they probably won't document it.
 *
 * To run this on a production server, you will need to start the artisan command as a daemon using Supervisor or similar.
 *
 * @link https://github.com/bluesky-social/jetstream
 */
class JetstreamServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:ws {cmd} {--H|host=jetstream1.us-west.bsky.network} {--C|collection=*} {--D|did=*} {--M|max=0 : Set maxMessageSizeBytes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Connect to Jetstream websocket server';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(JetstreamServer $jetstream): int
    {
        $jetstream->withCommand($this)
            ->start($this->option('collection'), $this->option('did'));

        Worker::runAll();

        return 0;
    }
}
