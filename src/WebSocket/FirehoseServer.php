<?php

declare(strict_types=1);

namespace Revolution\Bluesky\WebSocket;

use Illuminate\Console\Command;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Revolution\Bluesky\Core\CAR;
use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CID;
use Revolution\Bluesky\Events\Firehose\FirehoseAccountMessage;
use Revolution\Bluesky\Events\Firehose\FirehoseCommitMessage;
use Revolution\Bluesky\Events\Firehose\FirehoseIdentityMessage;
use Revolution\Bluesky\Events\Firehose\FirehoseMessageReceived;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Worker;

/**
 * @internal
 */
final class FirehoseServer
{
    use InteractsWithIO;

    protected bool $with_command = false;

    protected string $host = '';

    protected const KINDS = [
        '#commit',
        '#identity',
        '#account',

        // DEPRECATED
        // '#handle',
        // '#migrate',
        // '#tombstone',
        // '#info',
    ];

    protected const ACTIONS = [
        'create',
        'update',
        'delete',
    ];

    public function withCommand(Command $command): self
    {
        $this->with_command = true;

        $this->setOutput($command->getOutput());

        return $this;
    }

    public function start(): void
    {
        $this->host = Config::string('bluesky.firehose.host');

        $worker = new Worker();

        $worker->onWorkerStart = function ($worker) {
            $uri = 'ws://'.$this->host.':443/xrpc/com.atproto.sync.subscribeRepos';

            $con = new AsyncTcpConnection($uri);

            $con->transport = 'ssl';

            $con->onWebSocketConnect = function (AsyncTcpConnection $con) {
                $this->log('Host : '.$this->host);
            };

            $con->onMessage = $this->onMessage(...);

            $con->connect();
        };
    }

    private function onMessage(AsyncTcpConnection $con, string $data): void
    {
        /** @var array{t: string, op: int} $header */
        [$header, $remainder] = rescue(fn () => CBOR::decodeFirst($data), [[], '']);

        if (! Arr::has($header, ['t', 'op'])) {
            $this->log('header', $header);

            return;
        }

        if ($header['op'] !== 1) {
            return;
        }

        $kind = $header['t'];

        if (! in_array($kind, self::KINDS, true)) {
            return;
        }

        $payload = rescue(fn () => CBOR::decode($remainder ?? []));

        if ($kind === '#commit' && ! Arr::has($payload, ['tooBig'])) {
            $this->log('payload', Arr::set($payload, 'blocks', '...Invalid payload...'));

            return;
        }

        // Event processing subdivided by kind
        match ($kind) {
            '#commit' => $this->commit($header, $payload, $data),
            '#identity' => $this->identity($header, $payload, $data),
            '#account' => $this->account($header, $payload, $data),
        };

        // Finally, dispatch the raw data event
        event(new FirehoseMessageReceived($header, $payload, $data));
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
            $collection = '';
            $rkey = '';
            if (str_contains($path, '/')) {
                [$collection, $rkey] = explode('/', $path);
            }

            $record = $blocks[$path] ?? [];

            if ($this->with_command && $this->output->isVerbose()) {
                $value = $record['value'] ?? null;

                if (! empty($cid) && ! empty($value)) {
                    $this->log('commit', $record);

                    if (CID::verify(CBOR::encode($value), $cid, codec: CID::DAG_CBOR)) {
                        $this->log('Verified: '.$cid);
                    } else {
                        $this->log('Failed: '.$cid, CID::encode(CBOR::encode($record), codec: CID::DAG_CBOR));
                    }
                }
            }

            event(new FirehoseCommitMessage($did, $action, $time, $cid, $path, $collection, $rkey, $record, $payload, $raw));
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

        $this->log('identity', $payload);

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

        $this->log('account', $payload);

        $did = $payload['did'];
        $seq = $payload['seq'];
        $time = $payload['time'];
        $active = $payload['active'];
        $status = $payload['status'] ?? null;

        event(new FirehoseAccountMessage($did, $seq, $time, $active, $status, $raw));
    }

    public function log(string $message, null|array|string|int $context = null): void
    {
        if ($this->with_command && $this->getOutput()->isVerbose()) {
            $this->line($message.' '.Collection::wrap($context)->toJson());

            Log::build(config('bluesky.firehose.logging', []))->info($message, Arr::wrap($context));
        }
    }
}
