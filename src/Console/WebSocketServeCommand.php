<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CID;
use Revolution\Bluesky\Events\WebSocketMessageReceived;
use Valtzu\WebSocketMiddleware\WebSocketMiddleware;
use Valtzu\WebSocketMiddleware\WebSocketStream;

/**
 * When message is received, dispatch {@link WebSocketMessageReceived} event.
 *
 * ```
 * // No filters. Receive all messages.
 * php artisan bluesky:ws
 * ```
 * ```
 * // Filter to a specific collection type. To specify multiple collections, use multiple "-C".
 * php artisan bluesky:ws -C app.bsky.feed.post -C app.bsky.feed.like -C ...
 * ```
 * ```
 * // To filter by user, specify DID
 * php artisan bluesky:ws -C app.bsky.feed.post -D did:plc:... -D did:...
 * ```
 * ```
 * // Specify the host
 * php artisan bluesky:ws -H jetstream2.us-east.bsky.network
 * ```
 * ```
 * // Set maxMessageSizeBytes
 * php artisan bluesky:ws -M 1000000
 * ```
 * ```
 * // Display all received messages for debugging.
 * php artisan bluesky:ws -v
 * ```
 *
 * This is an advanced usage so they probably won't document it.
 *
 * To run this on a production server, you will need to start the artisan command as a daemon using Supervisor or similar.
 *
 * @link https://github.com/bluesky-social/jetstream
 */
class WebSocketServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:ws {--H|host=jetstream1.us-west.bsky.network} {--C|collection=*} {--D|did=*} {--M|max=0 : Set maxMessageSizeBytes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Connect to Jetstream websocket server';

    protected bool $running = true;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $handlerStack = new HandlerStack(new StreamHandler());
        $handlerStack->push(new WebSocketMiddleware());
        $client = new Client(['handler' => $handlerStack]);

        $host = (string) $this->option('host');

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

        $payload = [
            'wantedCollections' => $wantedCollections,
            'wantedDids' => $wantedDids,
            'maxMessageSizeBytes' => $max,
        ];

        $options = collect($payload)
            ->reject(fn ($value) => empty($value))
            ->implode('&');

        $uri = 'wss://'.$host.'/subscribe?'.$options;

        $res = $client->get($uri);

        if ($res->getStatusCode() !== 101) {
            dd($res);
        }

        $ws = $res->getBody();

        if (! $ws instanceof WebSocketStream) {
            $this->error('Something WebSocket error');
            dd($ws);
        }

        $this->trap(SIGTERM, function () {
            $this->running = false;
        });

        $this->info('Host : '.$host);
        $this->info('Payload : '.$options);

        while ($this->running) {
            $event = rescue(fn () => $ws->read(), '');

            $message = json_decode($event, true);

            if (empty($message)) {
                continue;
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

            if (is_array($message) && Arr::has($message, ['did', 'kind'])) {
                WebSocketMessageReceived::dispatch($message, $host, $payload);
            }
        }

        return 0;
    }
}
