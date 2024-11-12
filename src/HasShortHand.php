<?php

namespace Revolution\Bluesky;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Attributes\KnownValues;
use Revolution\AtProto\Lexicon\Enum\Feed;
use Revolution\AtProto\Lexicon\Enum\Graph;
use Revolution\Bluesky\Record\Block;
use Revolution\Bluesky\Record\Follow;
use Revolution\Bluesky\Record\Like;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\Record\Profile;
use Revolution\Bluesky\Record\Repost;
use Revolution\Bluesky\Record\ThreadGate;
use Revolution\Bluesky\Record\UserList;
use Revolution\Bluesky\Record\UserListItem;
use Revolution\Bluesky\Support\AtUri;
use Revolution\Bluesky\Types\StrongRef;

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
    public function getAuthorFeed(#[Format('at-identifier')] ?string $actor = null, ?int $limit = 50, ?string $cursor = null, #[KnownValues(['posts_with_replies', 'posts_no_replies', 'posts_with_media', 'posts_and_author_threads'])] ?string $filter = 'posts_with_replies', ?bool $includePins = null): Response
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
    public function getActorFeeds(#[Format('at-identifier')] ?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
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
    public function getProfile(#[Format('at-identifier')] ?string $actor = null): Response
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
     * use Revolution\Bluesky\Record\Profile;
     *
     * Bluesky::upsertProfile(function(Profile $profile): Profile {
     *     $profile->displayName('new name')
     *             ->description('new description');
     *
     *     $profile->avatar(function(): array {
     *        return Bluesky::uploadBlob(Storage::get('test.png'), Storage::mimeType('test.png'))->json('blob');
     *     });
     *
     *     return $profile;
     * })
     * ```
     *
     * @param  callable(Profile $profile): Profile  $callback
     * @throws AuthenticationException
     */
    public function upsertProfile(callable $callback): Response
    {
        $response = $this->getRecord(
            repo: $this->assertDid(),
            collection: Profile::NSID,
            rkey: 'self',
        );

        $existing = Profile::fromArray($response->json('value'));

        $updated = $callback($existing) ?? $existing;

        return $this->putRecord(
            repo: $this->assertDid(),
            collection: Profile::NSID,
            rkey: 'self',
            record: $updated,
            swapRecord: $response->json('cid'),
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
    public function deletePost(#[Format('at-uri')] string $uri): Response
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

    public function getPostThread(#[Format('at-uri')] string $uri, ?int $depth = 6, ?int $parentHeight = 80): Response
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
    public function getPost(#[Format('at-uri')] string $uri, ?string $cid = null): Response
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
    public function getActorLikes(#[Format('at-identifier')] ?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
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
    public function deleteLike(#[Format('at-uri')] string $uri): Response
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
    public function deleteRepost(#[Format('at-uri')] string $uri): Response
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
    public function getRepostedBy(#[Format('at-uri')] string $uri, ?string $cid = null, ?int $limit = 50, ?string $cursor = null): Response
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
    public function getFollowers(#[Format('at-identifier')] ?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
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
    public function getFollows(#[Format('at-identifier')] ?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
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
    public function deleteFollow(#[Format('at-uri')] string $uri): Response
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
     * This will get you the "blob" of the video you uploaded.
     */
    public function getJobStatus(string $jobId): Response
    {
        return $this->client(auth: true)
            ->getJobStatus($jobId);
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
    public function unblock(#[Format('at-uri')] string $uri): Response
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

    public function mute(#[Format('at-identifier')] string $actor): Response
    {
        return $this->client(auth: true)
            ->muteActor(
                actor: $actor,
            );
    }

    public function unmute(#[Format('at-identifier')] string $actor): Response
    {
        return $this->client(auth: true)
            ->unmuteActor(
                actor: $actor,
            );
    }

    /**
     * @param  string  $list  AT-URI
     */
    public function muteModList(#[Format('at-uri')] string $list): Response
    {
        return $this->client(auth: true)
            ->muteActorList(
                list: $list,
            );
    }

    /**
     * @param  string  $list  AT-URI
     */
    public function unmuteModList(#[Format('at-uri')] string $list): Response
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
    public function blockModList(#[Format('at-uri')] string $list): Response
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
    public function unblockModList(#[Format('at-uri')] string $list): Response
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
     * Create a user list.
     *
     * ```
     * use Revolution\Bluesky\Record\UserList;
     * use Revolution\Bluesky\RichText\TextBuilder;
     * use Revolution\AtProto\Lexicon\Enum\ListPurpose;
     *
     * $description = TextBuilder::make(text: 'description')
     *                           ->newLine(2)
     *                           ->link(text: 'https://', uri: 'https://');
     *
     * $list = UserList::create()
     *                 ->name('name')
     *                 ->purpose(ListPurpose::Curatelist)
     *                 ->description($description->text)
     *                 ->descriptionFacets($description->facets);
     *
     * Bluesky::createList($list);
     * ```
     *
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
     * Get created lists.
     *
     * @throws AuthenticationException
     */
    public function getLists(#[Format('at-identifier')] ?string $actor = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->getLists(
                actor: $actor ?? $this->assertDid(),
                limit: $limit,
                cursor: $cursor,
            );
    }

    /**
     * Viewing a list.
     *
     * @param  string  $list  URI
     */
    public function getList(#[Format('at-uri')] string $list, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->getList(
                list: $list,
                limit: $limit,
                cursor: $cursor,
            );
    }

    /**
     * Add a user to a list.
     *
     * ```
     * use Revolution\Bluesky\Record\UserListItem;
     *
     * $item = UserListItem::create(did: 'did', list: 'at://');
     * Bluesky::createListItem($item);
     * ```
     *
     * @throws AuthenticationException
     */
    public function createListItem(UserListItem $item): Response
    {
        return $this->createRecord(
            repo: $this->assertDid(),
            collection: Graph::Listitem->value,
            record: $item,
        );
    }

    /**
     * Remove a user from a list.
     */
    public function deleteListItem(#[Format('at-uri')] string $uri): Response
    {
        $at = AtUri::parse($uri);

        if ($at->collection() !== Graph::Listitem->value) {
            throw new InvalidArgumentException();
        }

        return $this->deleteRecord(
            repo: $at->repo(),
            collection: $at->collection(),
            rkey: $at->rkey(),
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
     * ```
     * use Revolution\Bluesky\Record\ThreadGate;
     *
     * Bluesky::createThreadGate(post: 'at://.../app.bsky.feed.post/...', allow: [ThreadGate::mention(), ThreadGate::following() , ThreadGate::list('at://')]);
     * ```
     *
     * @throws AuthenticationException
     */
    public function createThreadGate(#[Format('at-uri')] string $post, ?array $allow): Response
    {
        $at = AtUri::parse($post);

        if ($at->collection() !== Feed::Post->value) {
            throw new InvalidArgumentException();
        }

        $gate = ThreadGate::create($post, $allow);

        return $this->createRecord(
            repo: $this->assertDid(),
            collection: Feed::Threadgate->value,
            record: $gate,
            rkey: $at->rkey(),
        );
    }

    /**
     * @param  string  $handle  e.g. "alice.test"
     */
    public function resolveHandle(#[Format('handle')] string $handle): Response
    {
        return $this->client(auth: false)
            ->resolveHandle(handle: $handle);
    }

    /**
     * @param  string  $handle  e.g. "alice.test"
     */
    public function updateHandle(#[Format('handle')] string $handle): Response
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
