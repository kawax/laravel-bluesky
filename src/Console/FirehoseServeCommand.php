<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Number;
use Revolution\Bluesky\Core\CAR;
use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CID;
use Revolution\Bluesky\Events\Firehose\FirehoseAccountMessage;
use Revolution\Bluesky\Events\Firehose\FirehoseCommitMessage;
use Revolution\Bluesky\Events\Firehose\FirehoseIdentityMessage;
use Revolution\Bluesky\Events\Firehose\FirehoseMessageReceived;
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

        // DEPRECATED
        '#handle',
        '#migrate',
        '#tombstone',
        '#info',
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
        $ws = $this->ws();

        $this->trap(SIGTERM, function () {
            $this->running = false;
        });

        while ($this->running) {
            $event = rescue(fn () => $ws->read(self::MAX_SIZE), '');

            // Firehose often receives incorrect data.
            if (ord($event) !== 0xA2) {
                continue;
            }

            [$header, $remainder] = rescue(fn () => CBOR::decodeFirst($event), [[], '']);

            if (data_get($header, 'op') !== 1) {
                continue;
            }

            if (blank($header) || ! Arr::isAssoc($header)) {
                if ($this->output->isVerbose()) {
                    // Frequent memory errors
                    dump(Number::abbreviate(memory_get_usage(), 2));

                    dump($header);
                }

                continue;
            }

            $payload = rescue(fn () => CBOR::decode($remainder ?? []));

            if (blank($payload) || ! Arr::isAssoc($payload)) {
                if ($this->output->isVerbose()) {
                    dump($payload);
                }
                continue;
            }

            /** @var string $kind */
            $kind = data_get($header, 't');

            if (! in_array($kind, self::KINDS, true)) {
                continue;
            }

            // Event processing subdivided by kind
            match ($kind) {
                '#commit' => $this->commit($header, $payload, $event),
                '#identity' => $this->identity($header, $payload, $event),
                '#account' => $this->account($header, $payload, $event),
                default => null,
            };

            // Finally, dispatch the raw data event
            if (Arr::has($header, ['t']) && Arr::has($payload, ['ops'])) {
                event(new FirehoseMessageReceived($header, $payload, $event));
            }
        }

        return 0;
    }

    private function commit(array $header, array $payload, string $raw): void
    {
        $did = data_get($payload, 'repo') ?? '';
        $rev = data_get($payload, 'rev') ?? '';
        $time = data_get($payload, 'time') ?? 0;

        $records = data_get($payload, 'blocks');

        $blocks = [];
        if (filled($records)) {
            $blocks = rescue(fn () => iterator_to_array(CAR::blockMap($records)));
        }

        /** @var array $ops */
        $ops = data_get($payload, 'ops') ?? [];

        foreach ($ops as $op) {
            $action = data_get($op, 'action') ?? '';
            if (! in_array($action, self::ACTIONS, true)) {
                return;
            }

            /** @var ?string $cid */
            $cid = data_get($op, 'cid');

            $path = data_get($op, 'path') ?? '';
            if (str_contains($path, '/')) {
                [$collection, $rkey] = explode('/', $path);
            }

            $record = collect($blocks)->get($path) ?? [];

            if ($this->output->isVeryVerbose()) {
                $value = data_get($record, 'value');

                if (filled($cid) && filled($value)) {
                    dump($record);

                    if (CID::verify(CBOR::encode($value), $cid, codec: CID::DAG_CBOR)) {
                        dump('Verified: '.$cid);
                    } else {
                        dump('Failed: '.$cid, CID::encode(CBOR::encode($record), codec: CID::DAG_CBOR));
                    }
                }
            }

            event(new FirehoseCommitMessage($did, $action, $time, $cid, $path, $record, $payload, $raw));
        }
    }

    private function identity(array $header, array $payload, string $raw): void
    {
        $did = data_get($payload, 'did');
        $seq = data_get($payload, 'seq');
        $time = data_get($payload, 'time');
        $handle = data_get($payload, 'handle');

        event(new FirehoseIdentityMessage($did, $seq, $time, $handle, $raw));
    }

    private function account(array $header, array $payload, string $raw): void
    {
        $did = data_get($payload, 'did');
        $seq = data_get($payload, 'seq');
        $time = data_get($payload, 'time');
        $active = data_get($payload, 'active');
        $status = data_get($payload, 'status');

        event(new FirehoseAccountMessage($did, $seq, $time, $active, $status, $raw));
    }

    private function ws(): WebSocketStream
    {
        $handlerStack = new HandlerStack(new StreamHandler());
        $handlerStack->push(new WebSocketMiddleware());
        $client = new Client(['handler' => $handlerStack]);

        $host = (string) $this->option('host');
        $this->info('Host : '.$host);

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

        return $ws;
    }
}
