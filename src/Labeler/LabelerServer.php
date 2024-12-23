<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Moderation as OzoneModeration;
use Revolution\Bluesky\Core\CBOR;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Protocols\Websocket;
use Workerman\Timer;
use Workerman\Worker;
use Illuminate\Http\Request as LaravelRequest;

final class LabelerServer
{
    protected const HEARTBEAT_TIME = 55;

    protected Worker $ws;

    public string $host = '127.0.0.1';

    public int $port = 7000;

    public int $count = 1;

    public function start(): void
    {
        $this->ws = new Worker('websocket://'.$this->host.':'.$this->port);

        $this->ws->count = $this->count;

        $this->ws->onWorkerStart = function (Worker $worker) {
            $http = new Worker('http://'.$this->host.':'.$this->port + 1);
            $http->reusePort = true;
            $http->count = 4;

            $http->onMessage = function (TcpConnection $connection, Request $request) {
                $path = $request->path();

                if ($path === '/xrpc/'.OzoneModeration::emitEvent) {
                    $req = LaravelRequest::create(
                        uri: $request->uri(),
                        method: $request->method(),
                        parameters: array_merge($request->get(), $request->post()),
                    );

                    try {
                        $token = Str::after($request->header('Authorization'), 'Bearer ');

                        $emitEvent = Labeler::emitEvent($req, $token);

                        // websocket
                        $this->createLabels($emitEvent);

                        // http response
                        $connection->send(new Response(200, [], $emitEvent->toJson()));
                    } catch (LabelerException $e) {
                        $connection->send(new Response(403, [], 'Forbidden'));
                    }
                } else {
                    $connection->send(new Response(404, [], 'Not Found'));
                }
            };

            $http->listen();

            Timer::add(10, function () use ($worker) {
                $time_now = time();
                foreach ($worker->connections as $connection) {
                    if (empty($connection->lastMessageTime)) {
                        /**
                         * @phpstan-ignore property.notFound
                         */
                        $connection->lastMessageTime = $time_now;
                        continue;
                    }
                    /**
                     * @phpstan-ignore-next-line
                     */
                    if ($time_now - $connection->lastMessageTime > self::HEARTBEAT_TIME) {
                        $connection->close();
                    }
                }
            });
        };

        $this->ws->onWebSocketConnect = function (TcpConnection $connection, Request $request) {
            $connection->websocketType = Websocket::BINARY_TYPE_ARRAYBUFFER;

            $cursor = $request->get('cursor');
            $cursor = is_null($cursor) ? null : intval($cursor);

            try {
                foreach (Labeler::subscribeLabels($cursor) as $label) {
                    if ($label instanceof LabelMessage) {
                        $connection->send($label->toBytes());
                    }
                }
            } catch (LabelerException $e) {
                $connection->send($e->toBytes());
            }
        };

        $this->ws->onMessage = function (TcpConnection $connection, string $data) {
            $connection->send($data);
        };

        Worker::runAll();
    }

    private function createLabels(EmitEventResponse $emitEvent): void
    {
        $uri = data_get($emitEvent->subject, 'uri');
        $cid = data_get($emitEvent->subject, 'cid');

        $createLabelVals = (array) data_get($emitEvent->event, 'createLabelVals');
        $negateLabelVals = (array) data_get($emitEvent->event, 'negateLabelVals');

        collect($createLabelVals)
            ->each(fn ($val) => $this->createLabel($uri, $cid, $val));

        collect($negateLabelVals)
            ->map(fn ($val) => $this->createLabel($uri, $cid, $val, true));
    }

    private function createLabel(string $uri, ?string $cid, string $val, ?bool $neg = false): void
    {
        $label = collect([
            'uri' => $uri,
            'cid' => $cid,
            'val' => $val,
            'src' => Config::string('bluesky.labeler.did'),
            'cts' => now()->toISOString(),
            'exp' => null,// TODO
        ])->reject(fn ($value) => is_null($value))
            ->when($neg, fn ($collection) => $collection->put('neg', true))
            ->toArray();

        $this->saveLabel($label);
    }

    private function saveLabel(array $label): void
    {
        $label = Labeler::signLabel($label);

        $seq = now()->timestamp;// TODO

        $this->emitLabel($seq, $label);
    }

    private function emitLabel(int $seq, array $label): void
    {
        $header = ['op' => 1, 't' => '#labels'];

        $body = [
            'seq' => $seq,
            'labels' => [$label],
        ];

        $bytes = CBOR::encode($header).CBOR::encode($body);

        foreach ($this->ws->connections as $ws) {
            $ws->websocketType = Websocket::BINARY_TYPE_ARRAYBUFFER;

            $ws->send($bytes);
        }
    }
}
