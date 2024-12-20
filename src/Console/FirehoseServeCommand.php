<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
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

            /** @var array{t: string, op: int} $header */
            [$header, $remainder] = rescue(fn () => CBOR::decodeFirst($event), [[], '']);

            if (! Arr::has($header, ['t', 'op'])) {
                if ($this->output->isVerbose()) {
                    dump($header);
                }

                continue;
            }

            if ($header['op'] !== 1) {
                continue;
            }

            $kind = $header['t'];

            if (! in_array($kind, self::KINDS, true)) {
                continue;
            }

            $payload = rescue(fn () => CBOR::decode($remainder ?? []));

            if ($kind === '#commit' && ! Arr::has($payload, ['tooBig'])) {
                if ($this->output->isVerbose()) {
                    dump(Arr::set($payload, 'blocks', '...Invalid payload...'));
                }

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
            event(new FirehoseMessageReceived($header, $payload, $event));
        }

        return 0;
    }

    /**
     * @param  array{ops: list<array{cid: ?string, path: string, action: string}>, rev: string,seq: int, prev: null, repo: string, time: string, blobs: array, since: string, blocks: array, commit: string, rebase: bool, tooBig: bool}  $payload
     */
    private function commit(array $header, array $payload, string $raw): void
    {
        $required = ['seq', 'rebase', 'tooBig', 'repo', 'commit', 'rev', 'since', 'blocks', 'ops', 'blobs', 'time'];
        if (! Arr::has($payload, $required)) {
            return;
        }

        $did = $payload['repo'];
        $rev = $payload['rev'];
        $time = $payload['time'];

        $records = $payload['blocks'];

        $blocks = [];
        if (! empty($records)) {
            $blocks = rescue(fn () => iterator_to_array(CAR::blockMap($records)));
        }

        $ops = $payload['ops'];

        foreach ($ops as $op) {
            if (! Arr::has($op, ['cid', 'path', 'action'])) {
                continue;
            }

            $action = $op['action'];
            if (! in_array($action, self::ACTIONS, true)) {
                return;
            }

            $cid = $op['cid'];

            $path = $op['path'];
            if (str_contains($path, '/')) {
                [$collection, $rkey] = explode('/', $path);
            }

            $record = $blocks[$path] ?? [];

            if ($this->output->isVeryVerbose()) {
                $value = $record['value'] ?? null;

                if (! empty($cid) && ! empty($value)) {
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

    /**
     * @param  array{did: string, seq: int, time: string, handle?: string}  $payload
     */
    private function identity(array $header, array $payload, string $raw): void
    {
        $required = ['did', 'seq', 'time'];
        if (! Arr::has($payload, $required)) {
            return;
        }

        if ($this->output->isVeryVerbose()) {
            dump($payload);
        }

        $did = $payload['did'];
        $seq = $payload['seq'];
        $time = $payload['time'];
        $handle = $payload['handle'] ?? '';

        event(new FirehoseIdentityMessage($did, $seq, $time, $handle, $raw));
    }

    /**
     * @param  array{did: string, seq: int, time: string, active: bool, status?: string}  $payload
     */
    private function account(array $header, array $payload, string $raw): void
    {
        $required = ['did', 'seq', 'time', 'active'];
        if (! Arr::has($payload, $required)) {
            return;
        }

        if ($this->output->isVeryVerbose()) {
            dump($payload);
        }

        $did = $payload['did'];
        $seq = $payload['seq'];
        $time = $payload['time'];
        $active = $payload['active'];
        $status = $payload['status'] ?? null;

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
