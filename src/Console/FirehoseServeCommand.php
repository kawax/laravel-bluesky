<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Number;
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
 * @link https://atproto.com/specs/event-stream
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

    protected const MAX_SIZE = 1024 * 1024 * 5;

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

        while ($this->running) {
            $event = $ws->read(self::MAX_SIZE);

            // Firehose often receives incorrect data.
            [$header, $remainder] = rescue(fn () => CBOR::decodeFirst($event));
            [$payload, $remainder] = rescue(fn () => CBOR::decodeFirst($remainder));

            if (blank($header) || ! is_array($header)) {
                if ($this->output->isVerbose()) {
                    // Frequent memory errors
                    dump(Number::abbreviate(memory_get_usage(), 2));

                    dump($header);
                }

                continue;
            }

            if (blank($payload) || ! is_array($payload)) {
                if ($this->output->isVerbose()) {
                    dump($payload);
                }
                continue;
            }

            if (strlen($remainder) !== 0) {
                if ($this->output->isVerbose()) {
                    dump($remainder);
                }
                continue;
            }

            $records = data_get($payload, 'blocks');
            $roots = [];
            $blocks = [];
            if (filled($records)) {
                [$roots, $blocks] = rescue(fn () => CAR::decode($records), fn () => [null, null]);

                if (empty($roots)) {
                    continue;
                }
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

                    $payload_cid = data_get($payload, 'ops.0.cid');
                    if (CID::verify(CBOR::encode($record), $payload_cid, codec: CID::DAG_CBOR)) {
                        dump('Verified: '.$payload_cid);
                    } else {
                        dump('Failed: '.$payload_cid, CID::encode(CBOR::encode($record), codec: CID::DAG_CBOR));
                    }
                }
            }

            if (Arr::has($header, ['t'])) {
                event(new FirehoseMessageReceived($header, $payload, $roots, $blocks, $record, $host, $event));
            }
        }

        return 0;
    }
}
