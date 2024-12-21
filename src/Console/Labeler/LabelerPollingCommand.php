<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console\Labeler;

use Illuminate\Console\Command;
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
    protected $description = 'Polling notifications for Labeler';

    protected const CACHE_KEY = 'bluesky:labeler:polling:cursor';

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

        $cursor = Cache::get(self::CACHE_KEY);

        $response = Bluesky::login(Config::string('bluesky.labeler.identifier'), Config::string('bluesky.labeler.password'))
            ->listNotifications(limit: $limit, cursor: $cursor);

        $cursor = $response->json('cursor');
        Cache::forever(self::CACHE_KEY, $cursor);

        /** @var array $notifications */
        $notifications = $response->json('notifications');

        foreach ($notifications as $notification) {
            $reason = data_get($notification, 'reason');

            if (in_array($reason, self::REASONS, true)) {
                event(new NotificationReceived($reason, $notification));
            }
        }

        return 0;
    }
}
