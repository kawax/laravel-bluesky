<?php

namespace Revolution\Bluesky;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Revolution\AtProto\Lexicon\Enum\Feed;
use Revolution\AtProto\Lexicon\Enum\Graph;
use Revolution\Bluesky\Record\Block;
use Revolution\Bluesky\Record\Like;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\Record\Follow;
use Revolution\Bluesky\Record\Repost;
use Revolution\Bluesky\Record\UserList;
use Revolution\Bluesky\Support\AtUri;
use Revolution\Bluesky\Support\StrongRef;

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
     * @throws AuthenticationException
     */
    public function getAuthorFeed(?string $actor = null, ?int $limit = 50, ?string $cursor = null, ?string $filter = 'posts_with_replies', ?bool $includePins = null): Response
    {
        return $this->client(auth: true)
            ->getAuthorFeed(
                actor: $actor ?? $this->assertDid(),
                limit: $limit,
                cursor: $cursor,
                filter: $filter,
                includePins: $includePins,
            );
    }

    /**
     * @param  string|null  $actor  DID or handle.
     * @throws AuthenticationException
     */
    public function getActorFeeds(?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->getActorFeeds(
                actor: $actor ?? $this->assertDid(),
                limit: $limit,
                cursor: $cursor,
            );
    }

    /**
     * @param  string|null  $actor  DID or handle.
     * @throws AuthenticationException
     */
    public function getProfile(?string $actor = null): Response
    {
        return $this->client(auth: true)
            ->getProfile(
                actor: $actor ?? $this->assertDid(),
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
     * @throws AuthenticationException
     */
    public function upsertProfile(callable $callback): Response
    {
        $existing = $this->getRecord(
            repo: $this->assertDid(),
            collection: 'app.bsky.actor.profile',
            rkey: 'self',
        )->collect('value');

        $updated = $callback($existing) ?? $existing;

        $updated->put('$type', 'app.bsky.actor.profile');

        return $this->putRecord(
            repo: $this->assertDid(),
            collection: 'app.bsky.actor.profile',
            rkey: 'self',
            record: $updated->toArray(),
            swapRecord: $existing['cid'] ?? null,
        );
    }

    /**
     * Create new post.
     * @throws AuthenticationException
     */
    public function post(Post|string $text): Response
    {
        $post = $text instanceof Post ? $text : Post::create($text);

        return $this->createRecord(
            repo: $this->assertDid(),
            collection: Feed::Post->value,
            record: $post,
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
        return $this->client(auth: true)
            ->getPostThread(
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
        return $this->client(auth: true)
            ->getPosts(
                uris: $uris,
            );
    }

    /**
     * @param  string|null  $actor  DID or handle.
     * @throws AuthenticationException
     */
    public function getActorLikes(?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->getActorLikes(
                actor: $actor ?? $this->assertDid(),
                limit: $limit,
                cursor: $cursor,
            );
    }

    /**
     * @throws AuthenticationException
     */
    public function like(Like|StrongRef $subject): Response
    {
        $like = $subject instanceof Like ? $subject : Like::create($subject);

        return $this->createRecord(
            repo: $this->assertDid(),
            collection: Feed::Like->value,
            record: $like,
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

    /**
     * @throws AuthenticationException
     */
    public function repost(Repost|StrongRef $subject): Response
    {
        $repost = $subject instanceof Repost ? $subject : Repost::create($subject);

        return $this->createRecord(
            repo: $this->assertDid(),
            collection: Feed::Repost->value,
            record: $repost,
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
        return $this->client(auth: true)
            ->getRepostedBy(
                uri: $uri,
                cid: $cid,
                limit: $limit,
                cursor: $cursor,
            );
    }

    /**
     * @param  string|null  $actor  DID or handle.
     * @throws AuthenticationException
     */
    public function getFollowers(?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->getFollowers(
                actor: $actor ?? $this->assertDid(),
                limit: $limit,
                cursor: $cursor,
            );
    }

    /**
     * @param  string|null  $actor  DID or handle.
     * @throws AuthenticationException
     */
    public function getFollows(?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->getFollows(
                actor: $actor ?? $this->assertDid(),
                limit: $limit,
                cursor: $cursor,
            );
    }

    /**
     * @throws AuthenticationException
     */
    public function follow(Follow|string $did): Response
    {
        $follow = $did instanceof Follow ? $did : Follow::create(did: $did);

        return $this->createRecord(
            repo: $this->assertDid(),
            collection: Graph::Follow->value,
            record: $follow,
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

    /**
     * @throws AuthenticationException
     */
    public function block(Block|string $did): Response
    {
        $block = $did instanceof Block ? $did : Block::create(did: $did);

        return $this->createRecord(
            repo: $this->assertDid(),
            collection: Graph::Block->value,
            record: $block,
        );
    }

    /**
     * @param  string  $uri  at://did:plc:.../app.bsky.graph.block/{rkey}
     * @throws AuthenticationException
     */
    public function unblock(string $uri): Response
    {
        $at = AtUri::parse($uri);

        if ($at->collection() !== Graph::Block->value) {
            throw new InvalidArgumentException();
        }

        return $this->deleteRecord(
            repo: $this->assertDid(),
            collection: $at->collection(),
            rkey: $at->rkey(),
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
     * @throws AuthenticationException
     */
    public function blockModList(string $list): Response
    {
        return $this->createRecord(
            repo: $this->assertDid(),
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
     * @throws AuthenticationException
     */
    public function unblockModList(string $list): Response
    {
        $blocked = $this->getList(
            list: $list,
            limit: 1,
        )->json('list.viewer.blocked');

        if (empty($blocked)) {
            return new Response(Http::response([], 404)->wait());
        }

        $at = AtUri::parse($blocked);

        return $this->deleteRecord(
            repo: $this->assertDid(),
            collection: $at->collection(),
            rkey: $at->rkey(),
        );
    }

    /**
     * @throws AuthenticationException
     */
    public function createList(UserList $list): Response
    {
        return $this->createRecord(
            repo: $this->assertDid(),
            collection: Graph::List->value,
            record: $list,
        );
    }

    /**
     * @throws AuthenticationException
     */
    public function getLists(?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->getLists(
                actor: $actor ?? $this->assertDid(),
                limit: $limit,
                cursor: $cursor,
            );
    }

    /**
     * @param  string  $list  URI
     */
    public function getList(string $list, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->getList(
                list: $list,
                limit: $limit,
                cursor: $cursor,
            );
    }

    public function getListMutes(?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->getListMutes(
                limit: $limit,
                cursor: $cursor,
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
