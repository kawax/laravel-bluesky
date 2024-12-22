<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console\Labeler;

use Illuminate\Console\Command;
use Revolution\Bluesky\Labeler\Server\LabelerServer;
use Workerman\Worker;

/**
 * ```
 * php artisan bluesky:labeler:server start
 * ```
 */
class LabelerServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:labeler:server {cmd} {--H|host=127.0.0.1} {--P|port=7000} {--d|daemon=}';

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
    public function handle(LabelerServer $server): int
    {
        if (! class_exists(Worker::class)) {
            $this->error('Please install workerman/workerman');

            return 1;
        }

        $host = (string) $this->option('host');
        $port = (int) $this->option('port');

        $server->start($host, $port);

        return 0;
    }
}
