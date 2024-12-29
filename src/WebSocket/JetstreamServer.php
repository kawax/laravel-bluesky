<?php

declare(strict_types=1);

namespace Revolution\Bluesky\WebSocket;

use Illuminate\Console\Command;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CID;
use Revolution\Bluesky\Events\Jetstream\JetstreamAccountMessage;
use Revolution\Bluesky\Events\Jetstream\JetstreamCommitMessage;
use Revolution\Bluesky\Events\Jetstream\JetstreamIdentityMessage;
use Revolution\Bluesky\Events\Jetstream\JetstreamMessageReceived;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Worker;

/**
 * @internal
 */
final class JetstreamServer
{
    use InteractsWithIO;

    protected bool $with_command = false;

    protected string $host = '';

    protected array $payload = [];

    protected const KINDS = [
        'commit',
        'identity',
        'account',
    ];

    public function withCommand(Command $command): self
    {
        $this->with_command = true;

        $this->setOutput($command->getOutput());

        return $this;
    }

    public function start(array $collections = [], array $dids = []): void
    {
        $this->host = Config::string('bluesky.jetstream.host');

        $wantedCollections = collect($collections)
            ->map(fn (string $value) => 'wantedCollections='.$value)
            ->implode('&');

        $wantedDids = collect($dids)
            ->map(fn (string $value) => 'wantedDids='.$value)
            ->implode('&');

        $max = Config::integer('bluesky.jetstream.max');
        $maxMessageSizeBytes = '';
        if ($max > 0) {
            $maxMessageSizeBytes = 'maxMessageSizeBytes='.$max;
        }

        $this->payload = [
            'wantedCollections' => $wantedCollections,
            'wantedDids' => $wantedDids,
            'maxMessageSizeBytes' => $maxMessageSizeBytes,
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
                $this->log('Host : '.$this->host);
                $this->log('Payload : '.$options);
            };

            $con->onMessage = $this->onMessage(...);

            $con->connect();
        };
    }

    private function onMessage(AsyncTcpConnection $con, string $data): void
    {
        $message = json_decode($data, true);

        if (! is_array($message) || ! Arr::has($message, ['did', 'kind'])) {
            return;
        }

        if ($this->with_command && $this->getOutput()->isVerbose()) {
            $this->log('message', $message);
            /** @var ?array $record */
            $record = data_get($message, 'commit.record');
            /** @var ?string $cid */
            $cid = data_get($message, 'commit.cid');
            if (! is_null($record) && ! is_null($cid)) {
                if (CID::verify(CBOR::encode($record), $cid)) {
                    $this->log('Verified: '.$cid);
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

    public function log(string $message, null|array|string|int $context = null): void
    {
        if ($this->with_command && $this->getOutput()->isVerbose()) {
            $this->line($message.' '.Collection::wrap($context)->toJson());

            Log::build(config('bluesky.jetstream.logging', []))->info($message, Arr::wrap($context));
        }
    }
}
