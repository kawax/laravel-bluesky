<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events\Jetstream;

use Illuminate\Foundation\Events\Dispatchable;
use Revolution\Bluesky\Console\WebSocket\JetstreamServeCommand;

/**
 * Dispatch from {@link JetstreamServeCommand}.
 *
 * ```
 * use Revolution\Bluesky\Events\Jetstream\JetstreamMessage;
 *
 * class Listener
 * {
 *     public function handle(JetstreamMessage $event): void
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
class JetstreamMessageReceived
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
