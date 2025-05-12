<?php

declare(strict_types=1);

namespace Tests\Feature\Client;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Mockery;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Actor;
use Revolution\AtProto\Lexicon\Enum\ListPurpose;
use Revolution\Bluesky\Agent\OAuthAgent;
use Revolution\Bluesky\BlueskyManager;
use Revolution\Bluesky\Crypto\JsonWebToken;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Block;
use Revolution\Bluesky\Record\Follow;
use Revolution\Bluesky\Record\Like;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\Record\Profile;
use Revolution\Bluesky\Record\Repost;
use Revolution\Bluesky\Record\UserList;
use Revolution\Bluesky\Session\LegacySession;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Socialite\Key\OAuthKey;
use Revolution\Bluesky\Support\DNS;
use Revolution\Bluesky\Support\Identity;
use Revolution\Bluesky\Traits\WithBluesky;
use Revolution\Bluesky\Types\BlobRef;
use Revolution\Bluesky\Types\ReplyRef;
use Revolution\Bluesky\Types\SelfLabels;
use Revolution\Bluesky\Types\StrongRef;
use Tests\TestCase;

class ClientTest extends TestCase
{
    protected array $session = ['accessJwt' => 'test', 'refreshJwt' => 'test', 'did' => 'test', 'handle' => 'handle', 'didDoc' => ['service' => [['id' => '#atproto_pds', 'serviceEndpoint' => 'https://pds']]]];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_login(): void
    {
        $this->session = [
            'did' => 'did:plc:test',
            'accessJwt' => JsonWebToken::encode(
                [],
                ['exp' => now()->addSeconds(3600)->timestamp],
                OAuthKey::load()->privatePEM(),
            ),
            'refreshJwt' => 'test',
        ];

        Http::fake(fn () => $this->session);

        $client = new BlueskyManager();

        $client->login(identifier: 'identifier', password: 'password');

        Http::assertSent(function (Request $request) {
            return $request['identifier'] === 'identifier';
        });

        $this->assertNotEmpty($client->agent()->session('accessJwt'));
        $this->assertTrue($client->check());
    }

    public function test_logout(): void
    {
        Http::fake(fn () => $this->session);

        $client = new BlueskyManager();

        $client->login(identifier: 'identifier', password: 'password');

        Http::assertSent(function (Request $request) {
            return $request['identifier'] === 'identifier';
        });

        $client->logout();

        $this->assertNull($client->agent());
        $this->assertFalse($client->check());
    }

    public function test_session(): void
    {
        Http::fake(fn () => $this->session);

        $client = new BlueskyManager();

        $client->login(identifier: 'identifier', password: 'password');

        $this->assertSame('test', $client->agent()->session('accessJwt'));
    }

    public function test_session_resume(): void
    {
        Http::fake(fn () => $this->session);

        $client = new BlueskyManager();

        $client->login(identifier: 'identifier', password: 'password');

        $session = $client->agent()->session()->toArray();

        $client->withToken(LegacySession::create($session));

        $this->assertSame('test', $client->agent()->session('accessJwt'));
    }

    public function test_feed(): void
    {
        $this->session = [
            'did' => 'did:plc:test',
            'accessJwt' => JsonWebToken::encode(
                [],
                ['exp' => now()->addSeconds(3600)->timestamp],
                OAuthKey::load()->privatePEM(),
            ),
            'refreshJwt' => 'test',
        ];

        Http::fakeSequence()
            ->push($this->session)
            ->push(['feed' => ['post' => []]]);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->when(Bluesky::check(), function () {
                return Bluesky::getAuthorFeed(limit: 10, cursor: '2024', filter: 'posts_with_media');
            });

        $this->assertTrue($response->collect()->has('feed'));
    }

    public function test_timeline(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['feed' => ['post' => []]]);

        $response = Bluesky::unless(Bluesky::check(), fn () => Bluesky::login(identifier: 'identifier', password: 'password'))
            ->getTimeline(limit: 10, cursor: '2024');

        $this->assertTrue($response->collect()->has('feed'));
    }

    public function test_post(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['uri' => 'at']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->post(text: 'test');

        $this->assertTrue($response->collect()->has('uri'));
    }

    public function test_post_message(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['uri' => 'at']);

        $m = Post::create()
            ->text('text')
            ->reply(ReplyRef::to(root: StrongRef::to(uri: '', cid: ''), parent: StrongRef::to(uri: '', cid: '')));

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->post(text: $m);

        $this->assertTrue($response->collect()->has('uri'));
    }

    public function test_upload_blob(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['blob' => '...']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->uploadBlob('test', 'image/png');

        $this->assertTrue($response->collect()->has('blob'));
    }

    public function test_resolve_handle(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['did' => 'test']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->resolveHandle(handle: 'alice.localhost');

        $this->assertTrue($response->collect()->has('did'));
        $this->assertSame('test', $response->json('did'));
    }

    public function test_get_profile(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['did' => 'test']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->getProfile(actor: 'test');

        $this->assertTrue($response->collect()->has('did'));
        $this->assertSame('test', $response->json('did'));
    }

    public function test_resolve_did_plc(): void
    {
        Http::fakeSequence()
            ->push(['id' => 'did:plc:test']);

        $response = (new Identity())->resolveDID(did: 'did:plc:test', cache: true);
        $response_cache = (new Identity())->resolveDID(did: 'did:plc:test', cache: true);

        $this->assertTrue($response->collect()->has('id'));
        $this->assertSame('did:plc:test', $response->json('id'));
        $this->assertTrue(cache()->has(Identity::CACHE_DID.'did:plc:test'));

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://plc.directory/did:plc:test';
        });
    }

    public function test_resolve_did_web(): void
    {
        Http::fakeSequence()
            ->push(['id' => 'did:web:localhost']);

        $response = Bluesky::identity()->resolveDID(did: 'did:web:localhost', cache: false);

        $this->assertTrue($response->collect()->has('id'));
        $this->assertSame('did:web:localhost', $response->json('id'));
        $this->assertFalse(cache()->has(Identity::CACHE_DID.'did:web:localhost'));

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://localhost/.well-known/did.json';
        });
    }

    public function test_resolve_did_unsupported(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Http::fake();

        $response = Bluesky::identity()->resolveDID(did: 'did:test:test');

        Http::assertNothingSent();
    }

    public function test_resolve_did_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Http::fake();

        $response = Bluesky::identity()->resolveDID(did: 'did:test');

        Http::assertNothingSent();
    }

    public function test_identity_resolve_handle_dns(): void
    {
        DNS::fake(txt: 'did=did:plc:1234');

        $did = Bluesky::identity()->resolveHandle('example.com');
        $did_cache = Bluesky::identity()->resolveHandle('example.com');

        $this->assertSame('did:plc:1234', $did);
        $this->assertSame('did:plc:1234', $did_cache);
    }

    public function test_identity_resolve_handle_wellknown(): void
    {
        DNS::fake(txt: '');

        Http::fakeSequence()
            ->push('did:plc:1234');

        $did = Bluesky::identity()->resolveHandle('example.com');

        $this->assertSame('did:plc:1234', $did);
    }

    public function test_identity_resolve_identity_handle(): void
    {
        DNS::fake(txt: 'did=did:web:example.com');

        Http::fakeSequence()
            ->push(['id' => 'did:web:example.com']);

        $response = Bluesky::identity()->resolveIdentity('example.com');

        $this->assertTrue($response->collect()->has('id'));
        $this->assertSame('did:web:example.com', $response->json('id'));

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://example.com/.well-known/did.json';
        });
    }

    public function test_identity_resolve_identity_did(): void
    {
        Http::fakeSequence()
            ->push(['id' => 'did:web:example.com']);

        $response = Bluesky::identity()->resolveIdentity('did:web:example.com');

        $this->assertTrue($response->collect()->has('id'));
        $this->assertSame('did:web:example.com', $response->json('id'));

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://example.com/.well-known/did.json';
        });
    }

    public function test_with_agent(): void
    {
        $agent = Bluesky::withAgent(OAuthAgent::create(OAuthSession::create()))
            ->agent();

        $this->assertInstanceOf(OAuthAgent::class, $agent);
    }

    public function test_client_identity(): void
    {
        $this->assertInstanceOf(Identity::class, Bluesky::identity());
    }

    public function test_legacy_session(): void
    {
        $session = new LegacySession($this->session);

        $this->assertArrayHasKey('accessJwt', $session->toArray());
        $this->assertSame('test', $session->toArray()['accessJwt']);
    }

    public function test_oauth_session(): void
    {
        $oauth = [
            'access_token' => 'test',
            'refresh_token' => 'test',
            'did' => 'test',
        ];

        $session = new OAuthSession($oauth);

        $this->assertArrayHasKey('access_token', $session->toArray());
        $this->assertSame('test', $session->toArray()['access_token']);
    }

    public function test_with_oauth(): void
    {
        $oauth = [
            'refresh_token' => 'test',
            'did' => 'did:plc:test',
        ];

        $session = OAuthSession::create($oauth);

        $client = Bluesky::withToken($session);

        $this->assertInstanceOf(OAuthAgent::class, $client->agent());
        $this->assertSame('did:plc:test', $client->agent()->did());
    }

    public function test_public_endpoint(): void
    {
        Http::fake();

        Bluesky::getProfile('did');

        Http::assertSentCount(1);
    }

    public function test_refresh_session(): void
    {
        $this->assertInstanceOf(BlueskyManager::class, Bluesky::refreshSession());
    }

    public function test_with_bluesky_trait(): void
    {
        $user = new class
        {
            use WithBluesky;

            protected function tokenForBluesky(): OAuthSession
            {
                return OAuthSession::create([
                    'refresh_token' => 'test',
                    'iss' => 'https://iss',
                ]);
            }
        };

        $this->assertInstanceOf(BlueskyManager::class, $user->bluesky());
    }

    public function test_send(): void
    {
        Http::fake(fn () => ['did' => 'did']);

        $response = Bluesky::send(api: Actor::getProfile, method: 'get', auth: false, params: ['actor' => 'test']);

        $this->assertSame('did', $response->json('did'));

        Http::assertSent(function (Request $request) {
            return $request['actor'] === 'test' && $request->method() === 'GET';
        });
    }

    public function test_send_callback(): void
    {
        Http::fake();

        $response = Bluesky::send(
            api: Actor::getProfile,
            method: 'POST',
            callback: function (PendingRequest $http) {
                $http->withBody('test');
            });

        Http::assertSent(function (Request $request) {
            return $request->body() === 'test' && $request->method() === 'POST';
        });
    }

    public function test_follow(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([]);

        $follow = Follow::create(did: 'did');

        $response = Bluesky::login('id', 'pass')->follow($follow);

        $this->assertTrue($response->successful());
    }

    public function test_like(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([]);

        $like = Like::create(StrongRef::to(uri: 'uri', cid: 'cid'));

        $response = Bluesky::login('id', 'pass')->like($like);

        $this->assertTrue($response->successful());
    }

    public function test_like_subject(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([]);

        $subject = StrongRef::to(uri: 'uri', cid: 'cid');

        $response = Bluesky::login('id', 'pass')->like($subject);

        $this->assertTrue($response->successful());
    }

    public function test_repost(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([]);

        $repost = Repost::create(StrongRef::to(uri: 'uri', cid: 'cid'));

        $response = Bluesky::login('id', 'pass')->repost($repost);

        $this->assertTrue($response->successful());
    }

    public function test_repost_subject(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([]);

        $subject = StrongRef::to(uri: 'uri', cid: 'cid');

        $response = Bluesky::login('id', 'pass')->repost($subject);

        $this->assertTrue($response->successful());
    }

    public function test_block(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([]);

        $block = Block::create(did: 'did');

        $this->assertSame('did', $block->toArray()['subject']);
    }

    public function test_user_list(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([]);

        $userlist = UserList::create()
            ->name('name')
            ->purpose(ListPurpose::Curatelist)
            ->description('description');

        $this->assertSame('name', $userlist->toArray()['name']);
    }

    public function test_upsert_profile(): void
    {
        $profile = Profile::fromArray([
            'displayName' => 'name',
        ]);

        Http::fakeSequence()
            ->push($this->session)
            ->push(['value' => $profile->toArray()])
            ->push([]);

        $response = Bluesky::login('id', 'pass')
            ->upsertProfile(function (Profile $profile) {
                $profile->displayName('test')
                    ->description('test')
                    ->avatar(fn () => BlobRef::make(link: '...', mimeType: 'image/png', size: 1000))
                    ->banner(BlobRef::make(link: '...', mimeType: 'image/png', size: 1000))
                    ->labels(SelfLabels::make([]))
                    ->joinedViaStarterPack(StrongRef::to(uri: 'uri', cid: 'cid'))
                    ->pinnedPost(StrongRef::to(uri: 'uri', cid: 'cid'));
            });

        $this->assertTrue($response->successful());
    }

    public function test_search_posts(): void
    {
        Http::fake();

        $response = Bluesky::searchPosts(
            q: 'q',
            sort: 'latest',
            since: '',
            until: '',
            mentions: '',
            author: '',
            lang: '',
            domain: '',
            url: '',
            tag: [],
            limit: 25,
            cursor: '',
        );

        $this->assertTrue($response->successful());
    }

    public function test_video_upload(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['token' => 'test'])
            ->push([]);

        $response = Bluesky::login('id', 'pass')
            ->uploadVideo(data: '', type: 'video/mp4');

        $this->assertTrue($response->successful());
    }

    public function test_video_status(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['token' => 'test'])
            ->push([]);

        $response = Bluesky::login('id', 'pass')
            ->getJobStatus(jobId: 'id');

        $this->assertTrue($response->successful());
    }

    public function test_video_limits(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['token' => 'test'])
            ->push([]);

        $response = Bluesky::login('id', 'pass')
            ->getUploadLimits();

        $this->assertTrue($response->successful());
    }

    public function test_public_client(): void
    {
        Http::fakeSequence()
            ->push([]);

        $response = Bluesky::public()
            ->getProfile(actor: '');

        $this->assertTrue($response->successful());
    }

    public function test_list_records(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['records' => [['uri' => 'at://test/collection/123']]]);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->listRecords(
                repo: 'did:plc:test',
                collection: 'app.bsky.feed.post',
                limit: 10,
                cursor: 'cursor123',
                reverse: true
            );

        $this->assertTrue($response->successful());
        $this->assertTrue($response->collect()->has('records'));
        $this->assertSame('at://test/collection/123', $response->json('records.0.uri'));

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'xrpc/com.atproto.repo.listRecords') &&
                   $request['repo'] === 'did:plc:test' &&
                   $request['collection'] === 'app.bsky.feed.post' &&
                   $request['limit'] === 10 &&
                   $request['cursor'] === 'cursor123' &&
                   $request['reverse'] === true;
        });
    }

    public function test_delete_record(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['uri' => 'at://did:plc:test/app.bsky.feed.post/123']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->deleteRecord(
                repo: 'did:plc:test',
                collection: 'app.bsky.feed.post',
                rkey: '123',
                swapRecord: 'cid123',
                swapCommit: 'commit123'
            );

        $this->assertTrue($response->successful());
        $this->assertTrue($response->collect()->has('uri'));
        $this->assertSame('at://did:plc:test/app.bsky.feed.post/123', $response->json('uri'));

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'xrpc/com.atproto.repo.deleteRecord') &&
                   $request['repo'] === 'did:plc:test' &&
                   $request['collection'] === 'app.bsky.feed.post' &&
                   $request['rkey'] === '123' &&
                   $request['swapRecord'] === 'cid123' &&
                   $request['swapCommit'] === 'commit123';
        });
    }

    public function test_get_post(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['uri' => 'at://did:plc:test/app.bsky.feed.post/123', 'value' => ['text' => 'Test post']]);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->getPost(uri: 'at://did:plc:test/app.bsky.feed.post/123');

        $this->assertTrue($response->successful());
        $this->assertTrue($response->collect()->has('uri'));
        $this->assertSame('at://did:plc:test/app.bsky.feed.post/123', $response->json('uri'));
        $this->assertSame('Test post', $response->json('value.text'));

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'xrpc/com.atproto.repo.getRecord') &&
                   $request['repo'] === 'did:plc:test' &&
                   $request['collection'] === 'app.bsky.feed.post' &&
                   $request['rkey'] === '123';
        });
    }

    public function test_get_posts(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['posts' => [
                ['uri' => 'at://did:plc:test/app.bsky.feed.post/123', 'value' => ['text' => 'First post']],
                ['uri' => 'at://did:plc:test/app.bsky.feed.post/456', 'value' => ['text' => 'Second post']]
            ]]);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->getPosts(uris: [
                'at://did:plc:test/app.bsky.feed.post/123',
                'at://did:plc:test/app.bsky.feed.post/456'
            ]);

        $this->assertTrue($response->successful());
        $this->assertTrue($response->collect()->has('posts'));
        $this->assertCount(2, $response->json('posts'));
        $this->assertSame('First post', $response->json('posts.0.value.text'));
        $this->assertSame('Second post', $response->json('posts.1.value.text'));

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'xrpc/app.bsky.feed.getPosts') &&
                   $request['uris'] === [
                       'at://did:plc:test/app.bsky.feed.post/123',
                       'at://did:plc:test/app.bsky.feed.post/456'
                   ];
        });
    }

    public function test_delete_post(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['uri' => 'at://did:plc:test/app.bsky.feed.post/123']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->deletePost(uri: 'at://did:plc:test/app.bsky.feed.post/123');

        $this->assertTrue($response->successful());
        $this->assertTrue($response->collect()->has('uri'));
        $this->assertSame('at://did:plc:test/app.bsky.feed.post/123', $response->json('uri'));

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'xrpc/com.atproto.repo.deleteRecord') &&
                   $request['repo'] === 'did:plc:test' &&
                   $request['collection'] === 'app.bsky.feed.post' &&
                   $request['rkey'] === '123';
        });
    }

    public function test_list_notifications(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([
                'notifications' => [
                    [
                        'uri' => 'at://did:plc:test/app.bsky.feed.like/123',
                        'reason' => 'like',
                        'isRead' => false
                    ]
                ]
            ]);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->listNotifications(
                reasons: ['like', 'mention'],
                limit: 20,
                priority: true,
                cursor: 'cursor123',
                seenAt: '2023-01-01T00:00:00Z'
            );

        $this->assertTrue($response->successful());
        $this->assertTrue($response->collect()->has('notifications'));
        $this->assertCount(1, $response->json('notifications'));
        $this->assertSame('like', $response->json('notifications.0.reason'));

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'xrpc/app.bsky.notification.listNotifications') &&
                   $request['reasons'] === ['like', 'mention'] &&
                   $request['limit'] === 20 &&
                   $request['priority'] === true &&
                   $request['cursor'] === 'cursor123' &&
                   $request['seenAt'] === '2023-01-01T00:00:00Z';
        });
    }

    public function test_count_unread_notifications(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['count' => 5]);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->countUnreadNotifications(
                priority: true,
                seenAt: '2023-01-01T00:00:00Z'
            );

        $this->assertTrue($response->successful());
        $this->assertTrue($response->collect()->has('count'));
        $this->assertSame(5, $response->json('count'));

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'xrpc/app.bsky.notification.getUnreadCount') &&
                   $request['priority'] === true &&
                   $request['seenAt'] === '2023-01-01T00:00:00Z';
        });
    }

    public function test_get_actor_likes(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([
                'likes' => [
                    [
                        'uri' => 'at://did:plc:test/app.bsky.feed.like/123',
                        'subject' => ['uri' => 'at://did:plc:test/app.bsky.feed.post/456'],
                        'createdAt' => '2023-01-01T00:00:00Z'
                    ]
                ]
            ]);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->getActorLikes(
                actor: 'did:plc:test',
                limit: 30,
                cursor: 'cursor123'
            );

        $this->assertTrue($response->successful());
        $this->assertTrue($response->collect()->has('likes'));
        $this->assertCount(1, $response->json('likes'));
        $this->assertSame('at://did:plc:test/app.bsky.feed.like/123', $response->json('likes.0.uri'));

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'xrpc/app.bsky.feed.getActorLikes') &&
                   $request['actor'] === 'did:plc:test' &&
                   $request['limit'] === 30 &&
                   $request['cursor'] === 'cursor123';
        });
    }

    public function test_delete_like(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['uri' => 'at://did:plc:test/app.bsky.feed.like/123']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->deleteLike(uri: 'at://did:plc:test/app.bsky.feed.like/123');

        $this->assertTrue($response->successful());
        $this->assertTrue($response->collect()->has('uri'));
        $this->assertSame('at://did:plc:test/app.bsky.feed.like/123', $response->json('uri'));

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'xrpc/com.atproto.repo.deleteRecord') &&
                   $request['repo'] === 'did:plc:test' &&
                   $request['collection'] === 'app.bsky.feed.like' &&
                   $request['rkey'] === '123';
        });
    }

    public function test_get_followers(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([
                'followers' => [
                    [
                        'did' => 'did:plc:follower1',
                        'handle' => 'follower1.bsky.social',
                        'displayName' => 'Follower One'
                    ]
                ]
            ]);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->getFollowers(
                actor: 'did:plc:test',
                limit: 25,
                cursor: 'cursor123'
            );

        $this->assertTrue($response->successful());
        $this->assertTrue($response->collect()->has('followers'));
        $this->assertCount(1, $response->json('followers'));
        $this->assertSame('did:plc:follower1', $response->json('followers.0.did'));

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'xrpc/app.bsky.graph.getFollowers') &&
                   $request['actor'] === 'did:plc:test' &&
                   $request['limit'] === 25 &&
                   $request['cursor'] === 'cursor123';
        });
    }

    public function test_get_follows(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([
                'follows' => [
                    [
                        'did' => 'did:plc:follow1',
                        'handle' => 'follow1.bsky.social',
                        'displayName' => 'Follow One'
                    ]
                ]
            ]);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->getFollows(
                actor: 'did:plc:test',
                limit: 25,
                cursor: 'cursor123'
            );

        $this->assertTrue($response->successful());
        $this->assertTrue($response->collect()->has('follows'));
        $this->assertCount(1, $response->json('follows'));
        $this->assertSame('did:plc:follow1', $response->json('follows.0.did'));

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'xrpc/app.bsky.graph.getFollows') &&
                   $request['actor'] === 'did:plc:test' &&
                   $request['limit'] === 25 &&
                   $request['cursor'] === 'cursor123';
        });
    }

    public function test_update_seen_notifications(): void
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['success' => true]);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->updateSeenNotifications(seenAt: '2023-01-01T00:00:00Z');

        $this->assertTrue($response->successful());
        $this->assertTrue($response->collect()->has('success'));
        $this->assertTrue($response->json('success'));

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'xrpc/app.bsky.notification.updateSeen') &&
                   $request['seenAt'] === '2023-01-01T00:00:00Z';
        });
    }
}
