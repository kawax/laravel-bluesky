<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler\Server;

use Revolution\Bluesky\Labeler\Labeler;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Websocket;
use Workerman\Timer;
use Workerman\Worker;

/**
 * WebSocket server for Labeler.
 *
 * @internal
 */
final class LabelerServer
{
    protected const HEARTBEAT_TIME = 55;

    protected Worker $ws;

    protected string $host = '127.0.0.1';

    protected int $port = 7000;

    protected int $count = 1;

    protected bool $useHeartbeat = false;

    public function start(?string $host = null, ?int $port = null): void
    {
        if (! is_null($host)) {
            $this->host = $host;
        }

        if (! is_null($port)) {
            $this->port = $port;
        }

        $this->ws = new Worker('websocket://'.$this->host.':'.$this->port);

        $this->ws->count = 1;

        $this->ws->onWorkerStart = $this->onWorkerStart(...);

        $this->ws->onWebSocketConnected = $this->onWebSocketConnected(...);

        $this->ws->onMessage = $this->onMessage(...);

        Worker::runAll();
    }

    private function onWorkerStart(Worker $worker): void
    {
        $http = new Worker('http://'.$this->host.':'.$this->port + 1);
        $http->reusePort = true;
        $http->count = $this->count;

        $http->onMessage = (new HttpServer($this->ws))->onMessage(...);

        $http->listen();

        if ($this->useHeartbeat) {
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
        }
    }

    private function onWebSocketConnected(TcpConnection $connection, Request $request): void
    {
        $connection->websocketType = Websocket::BINARY_TYPE_ARRAYBUFFER;

        $cursor = $request->get('cursor');
        $cursor = is_null($cursor) ? null : intval($cursor);
        //info('subscribeLabels cursor: '.$cursor);

        foreach (Labeler::subscribeLabels($cursor) as $label) {
            $bytes = $label->toBytes();
            //info('subscribeLabels: '.$bytes);
            $connection->send($bytes);
        }
    }

    private function onMessage(TcpConnection $connection, string $data): void
    {
        //info('onMessage: ', Arr::wrap($data));

        $connection->send($data);
    }
}
