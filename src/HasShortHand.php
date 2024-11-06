<?php

namespace Revolution\Bluesky;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Revolution\AtProto\Lexicon\Enum\Feed;
use Revolution\Bluesky\Notifications\BlueskyMessage;
use Revolution\Bluesky\Support\Identity;

trait HasShortHand
{
    /**
     * @param  string|null  $actor  DID or handle.
     */
    public function profile(?string $actor = null): Response
    {
        return $this->client(auth: false)
            ->getProfile(
                actor: $actor ?? $this->agent()?->did() ?? '',
            );
    }

    /**
     * ```
     * use Illuminate\Support\Collection;
     *
     * Bluesky::upsertProfile(function(Collection $profile) {
     *     $profile->put('description', 'new description');
     *
     *     return $profile;
     * })
     * ```
     */
    public function upsertProfile(callable $callback): Response
    {
        $existing = $this->client(auth: true)->getRecord(
            repo: $this->agent()->did(),
            collection: 'app.bsky.actor.profile',
            rkey: 'self',
        )->collect('value');

        /** @var Collection $updated */
        $updated = $callback($existing);

        $updated->put('$type', 'app.bsky.actor.profile');

        return $this->client(auth: true)->putRecord(
            repo: $this->agent()->did(),
            collection: 'app.bsky.actor.profile',
            rkey: 'self',
            record: $updated->toArray(),
            swapRecord: $existing['cid'] ?? null,
        );
    }

    /**
     * getAuthorFeed.
     *
     * @param  string|null  $actor  DID or handle.
     */
    public function feed(?string $actor = null, int $limit = 50, string $cursor = '', string $filter = 'posts_with_replies'): Response
    {
        return $this->client(auth: false)
            ->getAuthorFeed(
                actor: $actor ?? $this->agent()?->did() ?? '',
                limit: $limit,
                cursor: $cursor,
                filter: $filter,
            );
    }

    /**
     * My timeline.
     */
    public function timeline(int $limit = 50, string $cursor = ''): Response
    {
        return $this->client(auth: true)
            ->getTimeline(
                limit: $limit,
                cursor: $cursor,
            );
    }

    public function createRecord(string $repo, string $collection, array $record): Response
    {
        return $this->client(auth: true)
            ->createRecord(
                repo: $repo,
                collection: $collection,
                record: $record,
            );
    }

    /**
     * Create new post.
     */
    public function post(string|BlueskyMessage $text): Response
    {
        $message = $text instanceof BlueskyMessage ? $text : BlueskyMessage::create($text);

        return $this->createRecord(
            repo: $this->agent()->did(),
            collection: Feed::Post->value,
            record: $message->toRecord(),
        );
    }

    public function like(string $uri, string $cid): Response
    {
        return $this->createRecord(
            repo: $this->agent()->did(),
            collection: Feed::Like->value,
            record: [
                '$type' => Feed::Like->value,
                'subject' => ['uri' => $uri, 'cid' => $cid],
                'createdAt' => now()->toISOString(),
            ],
        );
    }

    public function repost(string $uri, string $cid): Response
    {
        return $this->createRecord(
            repo: $this->agent()->did(),
            collection: Feed::Repost->value,
            record: [
                '$type' => Feed::Repost->value,
                'subject' => ['uri' => $uri, 'cid' => $cid],
                'createdAt' => now()->toISOString(),
            ],
        );
    }

    /**
     * Upload blob.
     */
    public function uploadBlob(mixed $data, string $type = 'image/png'): Response
    {
        return $this->client(auth: true)
            ->withBody($data, $type)
            ->uploadBlob();
    }

    /**
     * Upload video.
     */
    public function uploadVideo(mixed $data, string $type = 'video/mp4'): Response
    {
        return $this->client(auth: true)
            ->withBody($data, $type)
            ->uploadVideo();
    }

    /**
     * @param  string  $handle  e.g. "alice.test"
     */
    public function resolveHandle(string $handle): Response
    {
        if (! Identity::isHandle($handle)) {
            throw new InvalidArgumentException("The handle '$handle' is not a valid handle.");
        }

        return $this->client(auth: false)->resolveHandle(handle: $handle);
    }
}
