<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console\WebScoket;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CID;
use Revolution\Bluesky\Events\Jetstream\JetstreamAccountMessage;
use Revolution\Bluesky\Events\Jetstream\JetstreamCommitMessage;
use Revolution\Bluesky\Events\Jetstream\JetstreamIdentityMessage;
use Revolution\Bluesky\Events\Jetstream\JetstreamMessageReceived;
use Workerman\Connection\AsyncTcpConnection;
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

    protected string $host = '';

    protected array $payload = [];

    protected const KINDS = [
        'commit',
        'identity',
        'account',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->host = (string) $this->option('host');

        $wantedCollections = collect($this->option('collection'))
            ->map(fn ($did) => 'wantedCollections='.$did)
            ->implode('&');

        $wantedDids = collect($this->option('did'))
            ->map(fn ($did) => 'wantedDids='.$did)
            ->implode('&');

        $max = $this->option('max');
        if (! empty($max)) {
            $max = 'maxMessageSizeBytes='.$max;
        }

        $this->payload = [
            'wantedCollections' => $wantedCollections,
            'wantedDids' => $wantedDids,
            'maxMessageSizeBytes' => $max,
        ];

        $worker = new Worker();

        $worker->onWorkerStart = function ($worker) {
            $options = collect($this->payload)
                ->reject(fn ($value) => empty($value))
                ->implode('&');

            $uri = 'ws://'.$this->host.':443/subscribe?'.$options;

            $con = new AsyncTcpConnection($uri);

            $con->transport = 'ssl';

            $con->onWebSocketConnect = function (AsyncTcpConnection $con) use ($options) {
                $this->info('Host : '.$this->host);
                $this->info('Payload : '.$options);
            };

            $con->onMessage = $this->onMessage(...);

            $con->connect();
        };

        Worker::runAll();

        return 0;
    }

    private function onMessage(AsyncTcpConnection $con, string $data): void
    {
        $message = json_decode($data, true);

        if (! is_array($message) || ! Arr::has($message, ['did', 'kind'])) {
            return;
        }

        if ($this->output->isVerbose()) {
            dump($message);
            /** @var ?array $record */
            $record = data_get($message, 'commit.record');
            /** @var ?string $cid */
            $cid = data_get($message, 'commit.cid');
            if (! is_null($record) && ! is_null($cid)) {
                if (CID::verify(CBOR::encode($record), $cid)) {
                    $this->info('Verified: '.$cid);
                }
            }
        }

        /** @var string $kind */
        $kind = $message['kind'];

        if (! in_array($kind, self::KINDS, true)) {
            return;
        }

        match ($kind) {
            'commit' => $this->commit($kind, $message),
            'identity' => $this->identity($kind, $message),
            'account' => $this->account($kind, $message),
        };

        event(new JetstreamMessageReceived($message, $this->host, $this->payload));
    }

    private function commit(string $kind, array $message): void
    {
        $op = data_get($message, 'commit.operation');

        event(new JetstreamCommitMessage($kind, $op, $message, $this->host, $this->payload));
    }

    private function identity(string $kind, array $message): void
    {
        event(new JetstreamIdentityMessage($kind, $message, $this->host, $this->payload));
    }

    private function account(string $kind, array $message): void
    {
        event(new JetstreamAccountMessage($kind, $message, $this->host, $this->payload));
    }
}
