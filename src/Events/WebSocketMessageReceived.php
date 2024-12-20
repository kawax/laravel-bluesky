<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Revolution\Bluesky\Console\WebSocketServeCommand;

/**
 * Dispatch from {@link WebSocketServeCommand}.
 *
 * ```
 * use Revolution\Bluesky\Events\WebSocketMessageReceived;
 *
 * class Listener
 * {
 *     public function handle(WebSocketMessageReceived $event): void
 *     {
 *         $did = data_get($event->message, 'did');
 *         $kind = data_get($event->message, 'kind');
 *
 *         if($kind === 'commit') {
 *             $commit = data_get($event->message, $kind);
 *         }
 *     }
 * }
 * ```
 *
 * @link https://github.com/bluesky-social/jetstream
 */
class WebSocketMessageReceived
{
    use Dispatchable;

    public function __construct(
        public array $message,
        public string $host,
        public array $payload,
    ) {
        //
    }
}
