<?php

namespace Revolution\Bluesky;

use Illuminate\Http\Client\Response;
use InvalidArgumentException;
use Revolution\Bluesky\Client\AtpClient;
use Revolution\Bluesky\Contracts\XrpcClient;
use Revolution\Bluesky\Notifications\BlueskyMessage;
use Revolution\Bluesky\Support\Identity;

trait HasShortHand
{
    protected function client(bool $auth = true): XrpcClient|AtpClient
    {
        return app(XrpcClient::class)->withHttp($this->http($auth));
    }

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
            collection: 'app.bsky.feed.post',
            record: $message->toRecord(),
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
