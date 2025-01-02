<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler\Server;

use Revolution\Bluesky\Labeler\Labeler;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Websocket;
use Workerman\Worker;

/**
 * WebSocket server for Labeler.
 *
 * @internal
 */
final class LabelerServer
{
    protected Worker $ws;

    protected string $host = '127.0.0.1';

    protected int $port = 7000;

    public function start(?string $host = null, ?int $port = null): void
    {
        if (! is_null($host)) {
            $this->host = $host;
        }

        if (! is_null($port)) {
            $this->port = $port;
        }

        $this->ws = new Worker('websocket://'.$this->host.':'.$this->port);

        $this->ws->onWorkerStart = $this->onWorkerStart(...);

        $this->ws->onWebSocketConnected = $this->onWebSocketConnected(...);

        $this->ws->onMessage = $this->onMessage(...);
    }

    private function onWorkerStart(Worker $worker): void
    {
        $http = new Worker('http://'.$this->host.':'.$this->port + 1);
        $http->reusePort = true;

        $http->onMessage = (new HttpServer($this->ws))->onMessage(...);

        $http->listen();
    }

    private function onWebSocketConnected(TcpConnection $connection, Request $request): void
    {
        $connection->websocketType = Websocket::BINARY_TYPE_ARRAYBUFFER;

        $cursor = $request->get('cursor');
        $cursor = is_null($cursor) ? null : intval($cursor);

        Labeler::log('subscribeLabels cursor: ', $cursor);
        Labeler::log('subscribeLabels header: ', $request->header());

        foreach (Labeler::subscribeLabels($cursor) as $label) {
            $bytes = $label->toBytes();
            Labeler::log('subscribeLabels bytes: ', $bytes);
            $connection->send($bytes);
        }
    }

    private function onMessage(TcpConnection $connection, string $data): void
    {
        Labeler::log('onMessage: ', $data);

        $connection->send($data);
    }
}
