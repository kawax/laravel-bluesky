<?php

declare(strict_types=1);

namespace Revolution\Bluesky;

use BackedEnum;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Client\Response;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Http\Message\StreamInterface;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Attributes\KnownValues;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Actor as AtActor;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Feed as AtFeed;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Notification as AtNotification;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Video as AtVideo;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Identity as AtIdentity;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Repo as AtRepo;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Server as AtServer;
use Revolution\AtProto\Lexicon\Enum\Feed;
use Revolution\AtProto\Lexicon\Enum\Graph;
use Revolution\AtProto\Lexicon\Record\App\Bsky\Labeler\AbstractService;
use Revolution\Bluesky\Client\SubClient\VideoClient;
use Revolution\Bluesky\Contracts\Recordable;
use Revolution\Bluesky\Record\Follow;
use Revolution\Bluesky\Record\Generator;
use Revolution\Bluesky\Record\LabelerService;
use Revolution\Bluesky\Record\Like;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\Record\Profile;
use Revolution\Bluesky\Record\Repost;
use Revolution\Bluesky\Record\ThreadGate;
use Revolution\Bluesky\Support\AtUri;
use Revolution\Bluesky\Types\RepoRef;
use Revolution\Bluesky\Types\StrongRef;

use function Illuminate\Support\enum_value;

/**
 * The method names will be the same as the official client.
 *
 * @link https://github.com/bluesky-social/atproto/blob/main/packages/api/README.md
 */
trait HasShortHand
{
    #[ArrayShape(AtRepo::createRecordResponse)]
    public function createRecord(#[Format('at-identifier')] string $repo, #[Format('nsid')] string $collection, Recordable|array $record, ?string $rkey = null, ?bool $validate = null, ?string $swapCommit = null): Response
    {
        $record = $record instanceof Recordable ? $record->toRecord() : $record;

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

    #[ArrayShape(AtRepo::getRecordResponse)]
    public function getRecord(#[Format('at-identifier')] string $repo, #[Format('nsid')] string $collection, string $rkey, #[Format('cid')] ?string $cid = null): Response
    {
        return $this->client(auth: true)
            ->getRecord(
                repo: $repo,
                collection: $collection,
                rkey: $rkey,
                cid: $cid,
            );
    }

    #[ArrayShape(AtRepo::listRecordsResponse)]
    public function listRecords(#[Format('at-identifier')] string $repo, #[Format('nsid')] string $collection, ?int $limit = 50, ?string $cursor = null, ?bool $reverse = null): Response
    {
        return $this->client(auth: true)
            ->listRecords(
                repo: $repo,
                collection: $collection,
                limit: $limit,
                cursor: $cursor,
                reverse: $reverse,
            );
    }

    #[ArrayShape(AtRepo::putRecordResponse)]
    public function putRecord(#[Format('at-identifier')] string $repo, #[Format('nsid')] string $collection, string $rkey, Recordable|array $record, ?bool $validate = null, #[Format('cid')] ?string $swapRecord = null, #[Format('cid')] ?string $swapCommit = null): Response
    {
        $record = $record instanceof Recordable ? $record->toRecord() : $record;

        return $this->client(auth: true)
            ->putRecord(
                repo: $repo,
                collection: $collection,
                rkey: $rkey,
                record: $record,
                validate: $validate,
                swapRecord: $swapRecord,
                swapCommit: $swapCommit,
            );
    }

    #[ArrayShape(AtRepo::deleteRecordResponse)]
    public function deleteRecord(#[Format('at-identifier')] string $repo, #[Format('nsid')] string $collection, string $rkey, #[Format('cid')] ?string $swapRecord = null, #[Format('cid')] ?string $swapCommit = null): Response
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

    #[ArrayShape(AtFeed::getTimelineResponse)]
    public function getTimeline(?string $algorithm = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->bsky()
            ->getTimeline(
                algorithm: $algorithm,
                limit: $limit,
                cursor: $cursor,
            );
    }

    /**
     * @param  string|null  $actor  DID or handle
     *
     * @throws AuthenticationException
     */
    #[ArrayShape(AtFeed::getAuthorFeedResponse)]
    public function getAuthorFeed(#[Format('at-identifier')] ?string $actor = null, ?int $limit = 50, ?string $cursor = null, #[KnownValues(['posts_with_replies', 'posts_no_replies', 'posts_with_media', 'posts_and_author_threads'])] ?string $filter = 'posts_with_replies', ?bool $includePins = null): Response
    {
        return $this->client(auth: true)
            ->bsky()
            ->getAuthorFeed(
                actor: $actor ?? $this->assertDid(),
                limit: $limit,
                cursor: $cursor,
                filter: $filter,
                includePins: $includePins,
            );
    }

    #[ArrayShape(AtFeed::searchPostsResponse)]
    public function searchPosts(string $q, #[KnownValues(['top', 'latest'])] ?string $sort = 'latest', ?string $since = null, ?string $until = null, #[Format('at-identifier')] ?string $mentions = null, #[Format('at-identifier')] ?string $author = null, #[Format('language')] ?string $lang = null, ?string $domain = null, #[Format('uri')] ?string $url = null, ?array $tag = null, ?int $limit = 25, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->bsky()
            ->searchPosts(
                q: $q,
                sort: $sort,
                since: $since,
                until: $until,
                mentions: $mentions,
                author: $author,
                lang: $lang,
                domain: $domain,
                url: $url,
                tag: $tag,
                limit: $limit,
                cursor: $cursor,
            );
    }

    /**
     * @param  string|null  $actor  DID or handle
     *
     * @throws AuthenticationException
     */
    #[ArrayShape(AtActor::getProfileResponse)]
    public function getProfile(#[Format('at-identifier')] ?string $actor = null): Response
    {
        return $this->client(auth: true)
            ->bsky()
            ->getProfile(
                actor: $actor ?? $this->assertDid(),
            );
    }

    /**
     * Upsert Profile.
     *
     * ```
     * use Revolution\Bluesky\Record\Profile;
     *
     * $response = Bluesky::upsertProfile(function (Profile $profile) {
     *     $profile->displayName('new name')
     *             ->description('new description');
     *
     *     $profile->avatar(function (): array {
     *        return Bluesky::uploadBlob(Storage::get('test.png'), Storage::mimeType('test.png'))->json('blob');
     *     });
     * })
     * ```
     *
     * @param  callable(Profile $profile): mixed  $callback
     *
     * @throws AuthenticationException
     */
    #[ArrayShape(AtRepo::putRecordResponse)]
    public function upsertProfile(callable $callback): Response
    {
        $response = $this->getRecord(
            repo: $this->assertDid(),
            collection: Profile::NSID,
            rkey: 'self',
        );

        $profile = Profile::fromArray($response->json('value'))
            ->tap($callback);

        return $this->putRecord(
            repo: $this->assertDid(),
            collection: Profile::NSID,
            rkey: 'self',
            record: $profile,
            swapRecord: $response->json('cid'),
        );
    }

    /**
     * Create new post.
     *
     * @throws AuthenticationException
     */
    #[ArrayShape(AtRepo::createRecordResponse)]
    public function post(Post|string|array $text): Response
    {
        $post = is_string($text) ? Post::create($text) : $text;

        return $this->createRecord(
            repo: $this->assertDid(),
            collection: Feed::Post->value,
            record: $post,
        );
    }

    /**
     * @param  string  $uri  at://did:plc:.../app.bsky.feed.post/{rkey}
     */
    public function getPost(#[Format('at-uri')] string $uri, ?string $cid = null): Response
    {
        $at = AtUri::parse($uri);

        if ($at->collection() !== Feed::Post->value) {
            throw new InvalidArgumentException('uri is must start with "at://".');
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
     * @param  string  $uri  at://did:plc:.../app.bsky.feed.post/{rkey}
     */
    public function deletePost(#[Format('at-uri')] string $uri): Response
    {
        $at = AtUri::parse($uri);

        throw_if($at->collection() !== enum_value(Feed::Post), InvalidArgumentException::class);

        return $this->deleteRecord(
            repo: $at->repo(),
            collection: $at->collection(),
            rkey: $at->rkey(),
        );
    }

    /**
     * @param  string|null  $actor  DID or handle
     *
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
    #[ArrayShape(AtRepo::createRecordResponse)]
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

        throw_if($at->collection() !== enum_value(Feed::Like), InvalidArgumentException::class);

        return $this->deleteRecord(
            repo: $at->repo(),
            collection: $at->collection(),
            rkey: $at->rkey(),
        );
    }

    /**
     * @throws AuthenticationException
     */
    #[ArrayShape(AtRepo::createRecordResponse)]
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

        throw_if($at->collection() !== enum_value(Feed::Repost), InvalidArgumentException::class);

        return $this->deleteRecord(
            repo: $at->repo(),
            collection: $at->collection(),
            rkey: $at->rkey(),
        );
    }

    /**
     * @param  string|null  $actor  DID or handle
     *
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
     * @param  string|null  $actor  DID or handle
     *
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
    #[ArrayShape(AtRepo::createRecordResponse)]
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

        throw_if($at->collection() !== enum_value(Graph::Follow), InvalidArgumentException::class);

        return $this->deleteRecord(
            repo: $at->repo(),
            collection: $at->collection(),
            rkey: $at->rkey(),
        );
    }

    /**
     * Upload blob.
     */
    #[ArrayShape(AtRepo::uploadBlobResponse)]
    public function uploadBlob(StreamInterface|string $data, string $type = 'image/png'): Response
    {
        return $this->client(auth: true)
            ->withBody($data, $type)
            ->uploadBlob();
    }

    /**
     * Upload video.
     *
     * Bluesky::uploadVideo() returns a jobId, then you can use {@link getJobStatus()} to check if the upload is complete and retrieve the blob.
     *
     * ```
     * use Illuminate\Support\Facades\Storage;
     * use Revolution\Bluesky\Facades\Bluesky;
     *
     * $upload = Bluesky::withToken()->uploadVideo(Storage::get('video.mp4'));
     *
     * $jobId = $upload->json('jobId');
     *
     * $status = Bluesky::getJobStatus($jobId);
     *
     * if($status->json('jobStatus.state') === 'JOB_STATE_COMPLETED') {
     *     $blob = $status->json('jobStatus.blob');
     * }
     * ```
     *
     * @param  StreamInterface|string  $data  Video data
     * @param  string  $type  File mimetype
     *
     * @throws AuthenticationException
     */
    #[ArrayShape(['did' => 'string', 'error' => 'string', 'jobId' => 'string', 'message' => 'string', 'state' => 'string'])]
    public function uploadVideo(StreamInterface|string $data, #[KnownValues(['video/mp4', 'video/mpeg', 'video/webm', 'video/quicktime', 'image/gif'])] string $type = 'video/mp4'): Response
    {
        //Service auth is required to use the video upload features.
        $aud = $this->agent()?->session()->didDoc()->serviceAuthAud();
        throw_if(empty($aud), AuthenticationException::class);

        $token = $this->getServiceAuth(aud: $aud, exp: (int) now()->addMinutes(30)->timestamp, lxm: AtRepo::uploadBlob)
            ->json('token');

        return $this->client(auth: true)
            ->video($token)
            ->upload(
                did: $this->assertDid(),
                data: $data,
                type: $type,
            );
    }

    /**
     * This will get you the "blob" of the video you uploaded.
     */
    #[ArrayShape(AtVideo::getJobStatusResponse)]
    public function getJobStatus(string $jobId): Response
    {
        $aud = $this->agent()?->session()->didDoc()->serviceAuthAud();
        throw_if(empty($aud), AuthenticationException::class);

        $token = $this->getServiceAuth(aud: $aud, lxm: AtVideo::getJobStatus)
            ->json('token');

        return $this->client(auth: true)
            ->video($token)
            ->getJobStatus($jobId);
    }

    #[ArrayShape(AtVideo::getUploadLimitsResponse)]
    public function getUploadLimits(): Response
    {
        $token = $this->getServiceAuth(aud: VideoClient::VIDEO_SERVICE_DID, lxm: AtVideo::getUploadLimits)
            ->json('token');

        return $this->client(auth: true)
            ->video($token)
            ->getUploadLimits();
    }

    /**
     * Get service auth token.
     *
     * ```
     * $token = Bluesky::withToken()->getServiceAuth(aud: 'did:web:video.bsky.app', exp: now()->addMinutes(5)->timestamp, lxm: 'app.bsky.video.getUploadLimits')->json('token');
     * ```
     *
     * @param  string  $aud  The DID of the service that the token will be used to authenticate with
     * @param  int|null  $exp  The time in Unix Epoch seconds that the JWT expires. Defaults to 60 seconds in the future. The service may enforce certain time bounds on tokens depending on the requested scope.
     * @param  string|null  $lxm  Lexicon (XRPC) method to bind the requested token to
     */
    #[ArrayShape(AtServer::getServiceAuthResponse)]
    public function getServiceAuth(#[Format('did')] string $aud, ?int $exp = null, #[Format('nsid')] ?string $lxm = null): Response
    {
        return $this->client(auth: true)
            ->getServiceAuth(aud: $aud, exp: $exp, lxm: $lxm);
    }

    /**
     * Publish Feed Generator.
     * Run again to update your Feed Generator information.
     *
     * @param  BackedEnum|string  $name  Generator short name
     *
     * @throws AuthenticationException
     */
    #[ArrayShape(AtRepo::putRecordResponse)]
    public function publishFeedGenerator(BackedEnum|string $name, Generator $generator): Response
    {
        return $this->putRecord(
            repo: $this->assertDid(),
            collection: Feed::Generator->value,
            rkey: enum_value($name),
            record: $generator,
        );
    }

    /**
     * Create ThreadGate.
     *
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

        throw_if($at->collection() !== enum_value(Feed::Post), InvalidArgumentException::class);

        $gate = ThreadGate::create($post, $allow);

        return $this->createRecord(
            repo: $this->assertDid(),
            collection: Feed::Threadgate->value,
            record: $gate,
            rkey: $at->rkey(),
        );
    }

    /**
     * @param  string  $handle  `***.bsky.social` `alice.test`
     */
    #[ArrayShape(AtIdentity::resolveHandleResponse)]
    public function resolveHandle(#[Format('handle')] string $handle): Response
    {
        return $this->client(auth: false)
            ->resolveHandle(handle: $handle);
    }

    #[ArrayShape(AtNotification::listNotificationsResponse)]
    public function listNotifications(?array $reasons = null, ?int $limit = 50, ?bool $priority = null, ?string $cursor = null, ?string $seenAt = null): Response
    {
        return $this->client(auth: true)
            ->notification()
            ->listNotifications(
                reasons: $reasons,
                limit: $limit,
                priority: $priority,
                cursor: $cursor,
                seenAt: $seenAt,
            );
    }

    #[ArrayShape(AtNotification::getUnreadCountResponse)]
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

    /**
     * @param  callable(LabelerService $service): LabelerService  $callback
     */
    public function upsertLabelDefinitions(callable $callback): Response
    {
        $response = $this->getRecord(
            repo: $this->assertDid(),
            collection: AbstractService::NSID,
            rkey: 'self',
        );

        $service = LabelerService::fromArray($response->json('value'))->tap($callback);

        return $this->putRecord(
            repo: $this->assertDid(),
            collection: AbstractService::NSID,
            rkey: 'self',
            record: $service,
            swapRecord: $response->json('cid'),
        );
    }

    public function deleteLabelDefinitions(): Response
    {
        return $this->deleteRecord(
            repo: $this->assertDid(),
            collection: AbstractService::NSID,
            rkey: 'self',
        );
    }

    /**
     * Add label to account.
     * ```
     * use Revolution\Bluesky\Types\RepoRef;
     *
     * $response = Bluesky::login(config('bluesky.labeler.identifier'), config('bluesky.labeler.password'))
     *                    ->createLabels(RepoRef::to('did'), ['label1', 'label2']);
     * ```
     * Add label to record.
     * ```
     * use Revolution\Bluesky\Types\StrongRef;
     *
     * $response = Bluesky::login(config('bluesky.labeler.identifier'), config('bluesky.labeler.password'))
     *                     ->createLabels(StrongRef::to('uri', 'cid'), ['label1', 'label2']);
     *  ```
     */
    public function createLabels(RepoRef|StrongRef|array $subject, array $labels): Response
    {
        $event = [
            '$type' => 'tools.ozone.moderation.defs#modEventLabel',
            'createLabelVals' => $labels,
            'negateLabelVals' => [],
        ];

        return $this->emitEventLabel(
            event: $event,
            subject: $subject,
        );
    }

    public function deleteLabels(RepoRef|StrongRef|array $subject, array $labels): Response
    {
        $event = [
            '$type' => 'tools.ozone.moderation.defs#modEventLabel',
            'createLabelVals' => [],
            'negateLabelVals' => $labels,
        ];

        return $this->emitEventLabel(
            event: $event,
            subject: $subject,
        );
    }

    private function emitEventLabel(array $event, RepoRef|StrongRef|array $subject): Response
    {
        $labeler = $this->assertDid();

        $subject = $subject instanceof Arrayable ? $subject->toArray() : $subject;

        return $this->client(auth: true)
            ->ozone()
            ->withServiceProxy($labeler.'#atproto_labeler')
            ->emitEvent(
                event: $event,
                subject: $subject,
                createdBy: $labeler,
            );
    }
}
