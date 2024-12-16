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
use Revolution\Bluesky\Support\CAR;
use Revolution\Bluesky\Support\CBOR;
use Revolution\Bluesky\Support\CID;
use Valtzu\WebSocketMiddleware\WebSocketMiddleware;
use Valtzu\WebSocketMiddleware\WebSocketStream;

/**
 * Firehose is even more difficult than Jetstream WebSocket ({@link WebSocketServeCommand}) and is not expected to be commonly used, so there is no documentation.
 *
 * @link https://docs.bsky.app/docs/advanced-guides/firehose
 * @link https://atproto.com/ja/specs/event-stream
 */
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

            $payload_bytes = substr($event, strlen((string) $header));

            $header = $header->normalize();
            $payload = rescue(fn () => CBOR::normalize(CBOR::decode($payload_bytes)->normalize()));
            if (blank($payload) || ! is_array($payload)) {
                continue;
            }

            $records = data_get($payload, 'blocks');
            $roots = [];
            $blocks = [];
            if (filled($records)) {
                [$roots, $blocks] = CAR::decode($records);
            }

            $record = collect($blocks)->firstWhere('$type') ?? [];

            if ($this->output->isVeryVerbose()) {
                //dump($header);
                //dump($payload);

//                if (filled($roots)) {
//                    dump($roots);
//                }

//                if (filled($blocks)) {
//                    dump($blocks);
//                }

                if (filled($record) && data_get($record, '$type') === 'app.bsky.feed.post') {
                    dump($record);

                    // Verification fails if ref link is included
                    $payload_cid = data_get($payload, 'ops.0.cid');
                    if (CID::verify(CBOR::fromArray($record), $payload_cid, codec: CID::DAG_CBOR)) {
                        dump('Verified: '.$payload_cid);
                    } else {
                        dump('Failed: '.$payload_cid, CID::encode(CBOR::fromArray($record), codec: CID::DAG_CBOR));
                    }
                }
            }

            if (Arr::has($header, ['t'])) {
                event(new FirehoseMessageReceived($header, $payload, $roots, $blocks, $record, $host, $event));

                $event = null;
            }
        }

        return 0;
    }
}
