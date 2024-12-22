<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console\Labeler;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Revolution\Bluesky\Events\Labeler\NotificationReceived;
use Revolution\Bluesky\Facades\Bluesky;

/**
 * Execute this command periodically with the task scheduler.
 */
class LabelerPollingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:labeler:polling {--L|limit=50}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Polling Bluesky notifications for Labeler';

    protected const CACHE_KEY = 'bluesky:labeler:polling:seen';

    protected const REASONS = [
        'like',
        'repost',
        'follow',
        'mention',
        'reply',
        'quote',
        'starterpack-joined',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        /** @var string $seen */
        $seen = Cache::get(self::CACHE_KEY, now()->subDays(7)->toISOString());

        if ($this->output->isVerbose()) {
            $this->line('SeenAt: '.$seen);
        }

        Bluesky::login(Config::string('bluesky.labeler.identifier'), Config::string('bluesky.labeler.password'));

        $response = Bluesky::listNotifications(limit: $limit);

        if ($response->failed()) {
            $this->error($response->body());

            return 1;
        }

        $notifications = $response->collect('notifications');

        if ($this->output->isVerbose()) {
            $this->line('Count: '.$notifications->count());
        }

        $seen = Carbon::parse($seen);

        $notifications->each(function (array $notification) use ($seen) {
            $reason = data_get($notification, 'reason');
            $indexedAt = Carbon::parse(data_get($notification, 'indexedAt'));

            if ($indexedAt->gt($seen) && in_array($reason, self::REASONS, true)) {
                if ($this->output->isVerbose()) {
                    $this->line('Reason: '.$reason);
                }

                event(new NotificationReceived($reason, $notification));
            }
        });

        $seen = now()->toISOString();
        $res = Bluesky::updateSeenNotifications($seen);
        if ($res->failed()) {
            $this->error($res->body());

            return 1;
        }

        Cache::forever(self::CACHE_KEY, $seen);

        return 0;
    }
}
