<?php

namespace Revolution\Bluesky;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Revolution\AtProto\Lexicon\Enum\Feed;
use Revolution\AtProto\Lexicon\Enum\Graph;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\Record\Follow;
use Revolution\Bluesky\Support\AtUri;

/**
 * The method names will be the same as the official client.
 * https://github.com/bluesky-social/atproto/blob/main/packages/api/README.md
 */
trait HasShortHand
{
    public function getTimeline(?string $algorithm = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->getTimeline(
                algorithm: $algorithm,
                limit: $limit,
                cursor: $cursor,
            );
    }

    /**
     * @param  string|null  $actor  DID or handle.
     */
    public function getAuthorFeed(?string $actor = null, ?int $limit = 50, ?string $cursor = null, ?string $filter = 'posts_with_replies', ?bool $includePins = null): Response
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
     * @param  string|null  $actor  DID or handle.
     */
    public function getActorFeeds(?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->getActorFeeds(
                actor: $actor ?? $this->agent()->did(),
                limit: $limit,
                cursor: $cursor,
            );
    }

    /**
     * @param  string|null  $actor  DID or handle.
     */
    public function getProfile(?string $actor = null): Response
    {
        return $this->client(auth: true)
            ->getProfile(
                actor: $actor ?? $this->agent()?->did() ?? '',
            );
    }

    /**
     * @param  array<string>  $actors
     */
    public function getProfiles(array $actors): Response
    {
        return $this->client(auth: true)
            ->getProfiles(
                actors: $actors,
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
     *
     * @param  callable(Collection $profile): Collection  $callback
     */
    public function upsertProfile(callable $callback): Response
    {
        $existing = $this->client(auth: true)->getRecord(
            repo: $this->agent()->did(),
            collection: 'app.bsky.actor.profile',
            rkey: 'self',
        )->collect('value');

        $updated = $callback($existing) ?? $existing;

        $updated->put('$type', 'app.bsky.actor.profile');

        return $this->client(auth: true)->putRecord(
            repo: $this->agent()->did(),
            collection: 'app.bsky.actor.profile',
            rkey: 'self',
            record: $updated->toArray(),
            swapRecord: $existing['cid'] ?? null,
        );
    }

    public function createRecord(string $repo, string $collection, array $record, ?string $rkey = null, ?bool $validate = null, ?string $swapCommit = null): Response
    {
        return $this->client(auth: true)
            ->createRecord(
                repo: $repo,
                collection: $collection,
                record: $record,
                rkey: $rkey,
                validate: $validate,
                swapCommit: $swapCommit,
            );
    }

    public function getRecord(string $repo, string $collection, string $rkey, ?string $cid = null): Response
    {
        return $this->client(auth: true)
            ->getRecord(
                repo: $repo,
                collection: $collection,
                rkey: $rkey,
                cid: $cid,
            );
    }

    public function deleteRecord(string $repo, string $collection, string $rkey, ?string $swapRecord = null, ?string $swapCommit = null): Response
    {
        return $this->client(auth: true)
            ->deleteRecord(
                repo: $repo,
                collection: $collection,
                rkey: $rkey,
                swapRecord: $swapRecord,
                swapCommit: $swapCommit,
            );
    }

    /**
     * Create new post.
     */
    public function post(Post|string $text): Response
    {
        $post = $text instanceof Post ? $text : Post::create($text);

        return $this->createRecord(
            repo: $this->agent()->did(),
            collection: Feed::Post->value,
            record: $post->toRecord(),
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

    public function getPostThread(string $uri, ?int $depth = 6, ?int $parentHeight = 80): Response
    {
        return $this->client(auth: true)->getPostThread(
            uri: $uri,
            depth: $depth,
            parentHeight: $parentHeight,
        );
    }

    /**
     * @param  string  $uri  at://did:plc:.../app.bsky.feed.post/{rkey}
     */
    public function getPost(string $uri, ?string $cid = null): Response
    {
        $at = AtUri::parse($uri);

        if ($at->collection() !== Feed::Post->value) {
            throw new InvalidArgumentException();
        }

        return $this->getRecord(
            repo: $at->repo(),
            collection: $at->collection(),
            rkey: $at->rkey(),
            cid: $cid,
        );
    }

    /**
     * @param  array<string>  $uris  AT-URI
     */
    public function getPosts(array $uris): Response
    {
        return $this->client(auth: true)->getPosts(
            uris: $uris,
        );
    }

    /**
     * @param  string|null  $actor  DID or handle.
     */
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

    /**
     * @param  string  $uri  at://did:plc:.../app.bsky.feed.post/{rkey}
     */
    public function getRepostedBy(string $uri, ?string $cid = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)->getRepostedBy(
            uri: $uri,
            cid: $cid,
            limit: $limit,
            cursor: $cursor,
        );
    }

    /**
     * @param  string|null  $actor  DID or handle.
     */
    public function getFollowers(?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)->getFollowers(
            actor: $actor ?? $this->agent()->did(),
            limit: $limit,
            cursor: $cursor,
        );
    }

    /**
     * @param  string|null  $actor  DID or handle.
     */
    public function getFollows(?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)->getFollows(
            actor: $actor ?? $this->agent()->did(),
            limit: $limit,
            cursor: $cursor,
        );
    }

    public function follow(Follow|string $did): Response
    {
        $follow = $did instanceof Follow ? $did : Follow::create(did: $did);

        return $this->createRecord(
            repo: $this->agent()->did(),
            collection: Graph::Follow->value,
            record: $follow->toRecord(),
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

    public function getSuggestions(?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->getSuggestions(
                limit: $limit,
                cursor: $cursor,
            );
    }

    public function searchActors(?string $term = null, ?string $q = null, ?int $limit = 25, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->searchActors(
                term: $term,
                q: $q,
                limit: $limit,
                cursor: $cursor,
            );
    }

    public function searchActorsTypeahead(?string $term = null, ?string $q = null, ?int $limit = 10): Response
    {
        return $this->client(auth: true)
            ->searchActorsTypeahead(
                term: $term,
                q: $q,
                limit: $limit,
            );
    }

    public function mute(string $actor): Response
    {
        return $this->client(auth: true)
            ->muteActor(
                actor: $actor,
            );
    }

    public function unmute(string $actor): Response
    {
        return $this->client(auth: true)
            ->unmuteActor(
                actor: $actor,
            );
    }

    /**
     * @param  string  $list  AT-URI
     */
    public function muteModList(string $list): Response
    {
        return $this->client(auth: true)
            ->muteActorList(
                list: $list,
            );
    }

    /**
     * @param  string  $list  AT-URI
     */
    public function unmuteModList(string $list): Response
    {
        return $this->client(auth: true)
            ->unmuteActorList(
                list: $list,
            );
    }

    /**
     * @param  string  $list  AT-URI
     */
    public function blockModList(string $list): Response
    {
        return $this->createRecord(
            repo: $this->agent()->did(),
            collection: Graph::Listblock->value,
            record: [
                '$type' => Graph::Listblock->value,
                'subject' => $list,
                'createdAt' => now()->toISOString(),
            ],
        );
    }

    /**
     * @param  string  $list  AT-URI
     */
    public function unblockModList(string $list): Response
    {
        $blocked = $this->client(auth: true)->getList(
            list: $list,
            limit: 1,
        )->json('list.viewer.blocked');

        if (empty($blocked)) {
            return new Response(Http::response([], 404)->wait());
        }

        $at = AtUri::parse($blocked);

        return $this->deleteRecord(
            repo: $this->agent()->did(),
            collection: $at->collection(),
            rkey: $at->rkey(),
        );
    }

    /**
     * @param  string  $handle  e.g. "alice.test"
     */
    public function resolveHandle(string $handle): Response
    {
        return $this->client(auth: false)
            ->resolveHandle(handle: $handle);
    }

    /**
     * @param  string  $handle  e.g. "alice.test"
     */
    public function updateHandle(string $handle): Response
    {
        return $this->client(auth: true)
            ->updateHandle(
                handle: $handle,
            );
    }

    public function listNotifications(?int $limit = 50, ?bool $priority = null, ?string $cursor = null, ?string $seenAt = null): Response
    {
        return $this->client(auth: true)
            ->notification()
            ->listNotifications(
                limit: $limit,
                priority: $priority,
                cursor: $cursor,
                seenAt: $seenAt,
            );
    }

    public function countUnreadNotifications(?bool $priority = null, ?string $seenAt = null): Response
    {
        return $this->client(auth: true)
            ->notification()
            ->getUnreadCount(
                priority: $priority,
                seenAt: $seenAt,
            );
    }

    public function updateSeenNotifications(string $seenAt): Response
    {
        return $this->client(auth: true)
            ->notification()
            ->updateSeen(
                seenAt: $seenAt,
            );
    }
}
