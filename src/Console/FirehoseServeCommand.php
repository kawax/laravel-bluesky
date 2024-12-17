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

    protected const KINDS = [
        '#commit',
        '#identity',
        '#account',
    ];

    protected const ACTIONS = [
        'create',
        'update',
        'delete',
    ];

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

            if (data_get($header, 'op') !== 1) {
                continue;
            }

            $kind = data_get($header, 't');
            if (! in_array($kind, self::KINDS, true)) {
                continue;
            }

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
                [$roots, $blocks] = rescue(fn () => CAR::decode($records));

                if (empty($blocks)) {
                    //dump($blocks);
                    continue;
                }
            }

            $action = data_get($payload, 'ops.0.action');
            if (! in_array($action, self::ACTIONS, true)) {
                continue;
            }

            $did = data_get($payload, 'repo') ?? '';
            $rev = data_get($payload, 'rev') ?? '';
            $cid = data_get($payload, 'ops.0.cid') ?? '';
            $path = data_get($payload, 'ops.0.path') ?? '';
            [$collection, $rkey] = explode('/', $path);

            $record = collect($blocks)->get($path) ?? [];

            if ($this->output->isVeryVerbose()) {
                //dump($header);
                //dump($payload);

//                if (filled($roots)) {
//                    dump($roots);
//                }
//
//                if (filled($blocks)) {
//                    dump($blocks);
//                }

                $block = data_get($record, 'value');

                if (filled($block) && data_get($block, '$type') === 'app.bsky.feed.post') {
                    dump($record);

                    if (CID::verify(CBOR::encode($block), $cid, codec: CID::DAG_CBOR)) {
                        dump('Verified: '.$cid);
                    } else {
                        dump('Failed: '.$cid, CID::encode(CBOR::encode($record), codec: CID::DAG_CBOR));
                    }
                }
            }

            if (Arr::has($header, ['t'])) {
                event(new FirehoseMessageReceived(
                    $did,
                    $kind,
                    $action,
                    $cid,
                    $record,
                    $payload,
                    $host,
                    $event,
                ));
            }
        }

        return 0;
    }
}
