<?php

namespace Revolution\Bluesky;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Revolution\AtProto\Lexicon\Enum\Feed;
use Revolution\AtProto\Lexicon\Enum\Graph;
use Revolution\Bluesky\Notifications\BlueskyMessage;
use Revolution\Bluesky\Support\AtUri;
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
    public function feed(?string $actor = null, int $limit = 50, string $cursor = '', string $filter = 'posts_with_replies', ?bool $includePins = null): Response
    {
        return $this->client(auth: true)
            ->getAuthorFeed(
                actor: $actor ?? $this->agent()->did(),
                limit: $limit,
                cursor: $cursor,
                filter: $filter,
                includePins: $includePins,
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

    public function deleteRecord(string $repo, string $collection, string $rkey): Response
    {
        return $this->client(auth: true)
            ->deleteRecord(
                repo: $repo,
                collection: $collection,
                rkey: $rkey,
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

    /**
     * @param  string  $uri  at://did:plc:.../app.bsky.feed.post/{rkey}
     */
    public function deletePost(string $uri): Response
    {
        $at = AtUri::parse($uri);

        if ($at->collection() !== Feed::Post->value) {
            throw new InvalidArgumentException();
        }

        return $this->deleteRecord(
            repo: $at->repo(),
            collection: $at->collection(),
            rkey: $at->rkey(),
        );
    }

    public function getActorLikes(?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)->getActorLikes(
            actor: $actor ?? $this->agent()->did(),
            limit: $limit,
            cursor: $cursor,
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

    /**
     * uri can be obtained using getActorLikes().
     *
     * @param  string  $uri  at://did:plc:.../app.bsky.feed.like/{rkey}
     */
    public function deleteLike(string $uri): Response
    {
        $at = AtUri::parse($uri);

        if ($at->collection() !== Feed::Like->value) {
            throw new InvalidArgumentException();
        }

        return $this->deleteRecord(
            repo: $at->repo(),
            collection: $at->collection(),
            rkey: $at->rkey(),
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
     * @param  string  $uri  at://did:plc:.../app.bsky.feed.repost/{rkey}
     */
    public function deleteRepost(string $uri): Response
    {
        $at = AtUri::parse($uri);

        if ($at->collection() !== Feed::Repost->value) {
            throw new InvalidArgumentException();
        }

        return $this->deleteRecord(
            repo: $at->repo(),
            collection: $at->collection(),
            rkey: $at->rkey(),
        );
    }

    public function getFollowers(?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)->getFollowers(
            actor: $actor ?? $this->agent()->did(),
            limit: $limit,
            cursor: $cursor,
        );
    }

    public function getFollows(?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)->getFollows(
            actor: $actor ?? $this->agent()->did(),
            limit: $limit,
            cursor: $cursor,
        );
    }

    public function follow(string $did): Response
    {
        return $this->createRecord(
            repo: $this->agent()->did(),
            collection: Graph::Follow->value,
            record: [
                '$type' => Graph::Follow->value,
                'subject' => $did,
                'createdAt' => now()->toISOString(),
            ],
        );
    }

    /**
     * uri can be obtained using getFollows().
     *
     * @param  string  $uri  at://did:plc:.../app.bsky.graph.follow/{rkey}
     */
    public function deleteFollow(string $uri): Response
    {
        $at = AtUri::parse($uri);

        if ($at->collection() !== Graph::Follow->value) {
            throw new InvalidArgumentException();
        }

        return $this->deleteRecord(
            repo: $at->repo(),
            collection: $at->collection(),
            rkey: $at->rkey(),
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
