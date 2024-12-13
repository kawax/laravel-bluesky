<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use CBOR\MapObject;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Revolution\Bluesky\Events\FirehoseMessageReceived;
use Revolution\Bluesky\Support\CBOR;
use Valtzu\WebSocketMiddleware\WebSocketMiddleware;
use Valtzu\WebSocketMiddleware\WebSocketStream;

class FirehoseServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:firehose {--H|host=bsky.network}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Connect to Firehose websocket server';

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

        $uri = 'wss://'.$host.'/xrpc/com.atproto.sync.subscribeRepos';

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

        $event = null;

        while (! $ws->eof() || $this->running) {
            $event .= $ws->read();

            // Firehose often receives incorrect data.
            $header = rescue(fn () => CBOR::decode($event));
            if (blank($header) || ! $header instanceof MapObject) {
                if ($this->output->isVerbose()) {
                    dump($header);
                }

                continue;
            }

            $payload = substr($event, strlen((string) $header));

            $header = $header->normalize();
            $payload = rescue(fn () => CBOR::decode($payload)->normalize());

            if ($this->output->isVeryVerbose()) {
                //dump($header);
                dump($payload);
                $this->newLine();
            }

            if (Arr::has($header, ['t']) && is_array($payload)) {
                event(new FirehoseMessageReceived($header, $payload, $host, $event));
                $event = null;
            }
        }

        return 0;
    }
}
