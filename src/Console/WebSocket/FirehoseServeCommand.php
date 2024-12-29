<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console\WebSocket;

use Illuminate\Console\Command;
use Revolution\Bluesky\WebSocket\FirehoseServer;
use Workerman\Worker;

/**
 * Firehose is even more difficult than Jetstream WebSocket ({@link JetstreamServeCommand}) and is not expected to be commonly used, so there is no documentation.
 *
 * ```
 * php artisan bluesky:firehose start
 * ```
 *
 * @link https://docs.bsky.app/docs/advanced-guides/firehose
 * @link https://atproto.com/specs/event-stream
 */
class FirehoseServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:firehose {cmd}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Connect to Firehose websocket server';


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(FirehoseServer $firehose): int
    {
        if (! class_exists(Worker::class)) {
            $this->error('Please install workerman/workerman');

            return 1;
        }

        $firehose->withCommand($this)->start();

        Worker::runAll();

        return 0;
    }
}
