<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler\Server;

use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Moderation;
use Revolution\Bluesky\Labeler\EmitEventResponse;
use Revolution\Bluesky\Labeler\Labeler;
use Revolution\Bluesky\Labeler\LabelerException;
use Revolution\Bluesky\Labeler\SavedLabel;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Protocols\Websocket;
use Workerman\Worker;

/**
 * HttpServer in LabelerServer.
 *
 * @internal
 */
final class HttpServer
{
    public function __construct(
        protected Worker $ws,
    ) {
    }

    public function onMessage(TcpConnection $connection, Request $request): void
    {
        $path = $request->path();

        if (Str::endsWith($path, Moderation::emitEvent)) {
            $this->emitEvent($connection, $request);
        } elseif (Str::endsWith($path, '/xrpc/_health')) {
            $this->health($connection, $request);
        } else {
            $connection->send($this->json(['error' => 'Not Found'], status: 404));
        }
    }

    private function emitEvent(TcpConnection $connection, Request $request): void
    {
        $req = LaravelRequest::create(
            uri: $request->uri(),
            method: $request->method(),
            parameters: array_merge($request->get(), $request->post()),
        );

        try {
            $token = Str::after($request->header('Authorization'), 'Bearer ');

            $seq = 0;

            // websocket
            $iterator = Labeler::emitEvent($req, $token);
            foreach ($iterator as $unsigned) {
                [$signed, $sign] = Labeler::signLabel($unsigned);
                $savedLabel = Labeler::saveLabel($signed, $sign);
                $this->emitLabel($savedLabel);

                if ($savedLabel->id > $seq) {
                    $seq = $savedLabel->id;
                }
            }

            // http response
            $emitEvent = new EmitEventResponse(
                id: $seq - 1,
                event: $req->get('event'),
                subject: $req->get('subject'),
                createdBy: $req->get('createdBy'),
                subjectBlobCids: $req->get('subjectBlobCids', []),
                createdAt: now()->toISOString(),
            );

            $connection->send($this->json($emitEvent->toArray()));
        } catch (LabelerException) {
            $connection->send($this->json(['error' => 'Forbidden'], status: 403));
        }
    }

    private function emitLabel(SavedLabel $label): void
    {
        $bytes = $label->toBytes();

        //info('emitLabel: ', $label->toArray());
        //info('emitLabel bytes: '.$bytes);

        foreach ($this->ws->connections as $ws) {
            $ws->websocketType = Websocket::BINARY_TYPE_ARRAYBUFFER;

            $ws->send($bytes);
        }
    }

    private function health(TcpConnection $connection, Request $request): void
    {
        //info('health', Arr::wrap($request->header()));

        $connection->send($this->json(Labeler::health($request->header())));
    }

    private function json(array $data, int $status = 200): Response
    {
        return new Response($status, [
            'Content-Type' => 'application/json',
        ], json_encode($data));
    }
}
