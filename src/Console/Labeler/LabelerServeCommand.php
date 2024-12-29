<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console\Labeler;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Revolution\Bluesky\Labeler\Server\LabelerServer;
use Revolution\Bluesky\WebSocket\FirehoseServer;
use Revolution\Bluesky\WebSocket\JetstreamServer;
use Workerman\Worker;

/**
 * ```
 * php artisan bluesky:labeler:server start
 * ```
 * Working with Jetstream
 * ```
 * php artisan bluesky:labeler:server start --jetstream
 * php artisan bluesky:labeler:server start --jetstream -C app.bsky.graph.follow -C app.bsky.feed.like
 * ```
 * Working with Firehose
 * ```
 * php artisan bluesky:labeler:server start --firehose
 * ```
 */
class LabelerServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:labeler:server {cmd} {--jetstream} {--firehose} {--C|collection=*} {--D|did=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Labeler WebSocket server';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(LabelerServer $labeler, JetstreamServer $jetstream, FirehoseServer $firehose): int
    {
        if (! class_exists(Worker::class)) {
            $this->error('Please install workerman/workerman');

            return 1;
        }

        $host = Config::string('bluesky.labeler.host', '127.0.0.1');
        $port = Config::integer('bluesky.labeler.port', 7000);

        $labeler->start($host, $port);

        if ($this->option('jetstream')) {
            $jetstream->withCommand($this)->start($this->option('collection'), $this->option('did'));
        }

        if ($this->option('firehose')) {
            $firehose->withCommand($this)->start();
        }

        Worker::runAll();

        return 0;
    }
}
