<?php

namespace Revolution\Bluesky;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use InvalidArgumentException;
use Revolution\Bluesky\Client\AtpClient;
use Revolution\Bluesky\Client\BskyClient;
use Revolution\Bluesky\Lexicon\Contracts\App\Bsky\Video;
use Revolution\Bluesky\Lexicon\Contracts\Com\Atproto\Repo;
use Revolution\Bluesky\Notifications\BlueskyMessage;
use Revolution\Bluesky\Support\Identity;

trait HasShortHand
{
    protected function bsky(bool $auth = false): BskyClient
    {
        return app(BskyClient::class)->withHttp($this->http($auth));
    }

    protected function atp(bool $auth = true): AtpClient
    {
        return app(AtpClient::class)->withHttp($this->http($auth));
    }

    /**
     * @param  string|null  $actor  DID or handle.
     */
    public function profile(?string $actor = null): Response
    {
        return $this->bsky(auth: false)->getProfile(
            actor: $actor ?? $this->agent()?->did() ?? '',
        );
    }

    /**
     * getAuthorFeed.
     *
     * @param  string|null  $actor  DID or handle.
     */
    public function feed(?string $actor = null, int $limit = 50, string $cursor = '', string $filter = 'posts_with_replies'): Response
    {
        return $this->bsky(auth: false)->getAuthorFeed(
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
        return $this->bsky(auth: true)->getTimeline(
            limit: $limit,
            cursor: $cursor,
        );
    }

    public function createRecord(string $repo, string $collection, array $record): Response
    {
        return $this->atp(auth: true)->createRecord(
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

        $record = collect($message->toArray())
            ->put('createdAt', now()->toISOString())
            ->reject(fn ($item) => blank($item))
            ->toArray();

        return $this->createRecord(
            repo: $this->agent()->did(),
            collection: 'app.bsky.feed.post',
            record: $record,
        );
    }

    /**
     * Upload blob.
     *
     * @throws ConnectionException
     */
    public function uploadBlob(mixed $data, string $type = 'image/png'): Response
    {
        return $this->http()
            ->withBody($data, $type)
            ->post(Repo::uploadBlob);
    }

    /**
     * Upload video.
     *
     * @throws ConnectionException
     */
    public function uploadVideo(mixed $data, string $type = 'video/mp4'): Response
    {
        return $this->http()
            ->withBody($data, $type)
            ->post(Video::uploadVideo);
    }

    /**
     * @param  string  $handle  e.g. "alice.test"
     */
    public function resolveHandle(string $handle): Response
    {
        if (! Identity::isHandle($handle)) {
            throw new InvalidArgumentException("The handle '$handle' is not a valid handle.");
        }

        return $this->atp(auth: false)->resolveHandle(handle: $handle);
    }
}
