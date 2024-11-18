<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
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
 * // Display all received messages for debugging.
 * php artisan bluesky:ws -v
 * ```
 *
 * @see https://github.com/bluesky-social/jetstream
 */
class WebSocketServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:ws {--H|host=jetstream1.us-west.bsky.network} {--C|collection=*} {--D|did=*}';

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

        $host = $this->option('host');

        $res = $client->get('wss://'.$host.'/subscribe?requireHello=true');

        if ($res->getStatusCode() !== 101) {
            dd($res);
        }

        $ws = $res->getBody();

        if (! $ws instanceof WebSocketStream) {
            $this->error('Something WebSocket error');
            dd($ws);
        }

        $this->trap(SIGTERM, fn () => $this->running = false);

        $options_update = [
            'type' => 'options_update',
            'payload' => [
                'wantedCollections' => $this->option('collection'),
                'wantedDids' => $this->option('did'),
            ],
        ];

        $ws->write($options = json_encode($options_update));

        $this->info('Host : '.$host);
        $this->line('Payload : '.$options);

        while (! $ws->eof() || $this->running) {
            $event = $ws->read();

            if ($this->output->isVerbose()) {
                $this->line($event);
                $this->newLine();
            }

            $message = json_decode($event, true);

            if (is_array($message) && Arr::has($message, ['did', 'kind'])) {
                WebSocketMessageReceived::dispatch($message);
            }
        }

        return 0;
    }
}
