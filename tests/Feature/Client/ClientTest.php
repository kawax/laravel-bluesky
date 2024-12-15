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

    public function test_login()
    {
        Http::fake(fn () => $this->session);

        $client = new BlueskyManager();

        $client->login(identifier: 'identifier', password: 'password');

        Http::assertSent(function (Request $request) {
            return $request['identifier'] === 'identifier';
        });

        $this->assertSame('test', $client->agent()->session('accessJwt'));
        $this->assertTrue($client->check());
    }

    public function test_logout()
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

    public function test_session()
    {
        Http::fake(fn () => $this->session);

        $client = new BlueskyManager();

        $client->login(identifier: 'identifier', password: 'password');

        $this->assertIsArray($client->agent()->session()->toArray());
        $this->assertSame('test', $client->agent()->session('accessJwt'));
    }

    public function test_feed()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['feed' => ['post' => []]]);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->when(Bluesky::check(), function () {
                return Bluesky::getAuthorFeed(limit: 10, cursor: '2024', filter: 'posts_with_media');
            });

        $this->assertTrue($response->collect()->has('feed'));
    }

    public function test_timeline()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['feed' => ['post' => []]]);

        $response = Bluesky::unless(Bluesky::check(), fn () => Bluesky::login(identifier: 'identifier', password: 'password'))
            ->getTimeline(limit: 10, cursor: '2024');

        $this->assertTrue($response->collect()->has('feed'));
    }

    public function test_post()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['uri' => 'at']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->post(text: 'test');

        $this->assertTrue($response->collect()->has('uri'));
    }

    public function test_post_message()
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

    public function test_upload_blob()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['blob' => '...']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->uploadBlob('test', 'image/png');

        $this->assertTrue($response->collect()->has('blob'));
    }

    public function test_resolve_handle()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['did' => 'test']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->resolveHandle(handle: 'alice.localhost');

        $this->assertTrue($response->collect()->has('did'));
        $this->assertSame('test', $response->json('did'));
    }

    public function test_get_profile()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['did' => 'test']);

        $response = Bluesky::login(identifier: 'identifier', password: 'password')
            ->getProfile(actor: 'test');

        $this->assertTrue($response->collect()->has('did'));
        $this->assertSame('test', $response->json('did'));
    }

    public function test_resolve_did_plc()
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

    public function test_resolve_did_web()
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

    public function test_resolve_did_unsupported()
    {
        $this->expectException(InvalidArgumentException::class);

        Http::fake();

        $response = Bluesky::identity()->resolveDID(did: 'did:test:test');

        Http::assertNothingSent();
    }

    public function test_resolve_did_invalid()
    {
        $this->expectException(InvalidArgumentException::class);

        Http::fake();

        $response = Bluesky::identity()->resolveDID(did: 'did:test');

        Http::assertNothingSent();
    }

    public function test_identity_resolve_handle_dns()
    {
        DNS::fake(txt: 'did=did:plc:1234');

        $did = Bluesky::identity()->resolveHandle('example.com');
        $did_cache = Bluesky::identity()->resolveHandle('example.com');

        $this->assertSame('did:plc:1234', $did);
        $this->assertSame('did:plc:1234', $did_cache);
    }

    public function test_identity_resolve_handle_wellknown()
    {
        DNS::fake(txt: '');

        Http::fakeSequence()
            ->push('did:plc:1234');

        $did = Bluesky::identity()->resolveHandle('example.com');

        $this->assertSame('did:plc:1234', $did);
    }

    public function test_identity_resolve_identity_handle()
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

    public function test_identity_resolve_identity_did()
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

    public function test_with_agent()
    {
        $agent = Bluesky::withAgent(OAuthAgent::create(OAuthSession::create()))
            ->agent();

        $this->assertInstanceOf(OAuthAgent::class, $agent);
    }

    public function test_client_identity()
    {
        $this->assertInstanceOf(Identity::class, Bluesky::identity());
    }

    public function test_legacy_session()
    {
        $session = new LegacySession($this->session);

        $this->assertArrayHasKey('accessJwt', $session->toArray());
        $this->assertSame('test', $session->toArray()['accessJwt']);
    }

    public function test_oauth_session()
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

    public function test_with_oauth()
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

    public function test_public_endpoint()
    {
        Http::fake();

        Bluesky::getProfile('did');

        Http::assertSentCount(1);
    }

    public function test_refresh_session()
    {
        $this->assertInstanceOf(BlueskyManager::class, Bluesky::refreshSession());
    }

    public function test_with_bluesky_trait()
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

    public function test_send()
    {
        Http::fake(fn () => ['did' => 'did']);

        $response = Bluesky::send(api: Actor::getProfile, method: 'get', auth: false, params: ['actor' => 'test']);

        $this->assertSame('did', $response->json('did'));

        Http::assertSent(function (Request $request) {
            return $request['actor'] === 'test' && $request->method() === 'GET';
        });
    }

    public function test_send_callback()
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

    public function test_follow()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([]);

        $follow = Follow::create(did: 'did');

        $response = Bluesky::login('id', 'pass')->follow($follow);

        $this->assertTrue($response->successful());
    }

    public function test_like()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([]);

        $like = Like::create(StrongRef::to(uri: 'uri', cid: 'cid'));

        $response = Bluesky::login('id', 'pass')->like($like);

        $this->assertTrue($response->successful());
    }

    public function test_like_subject()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([]);

        $subject = StrongRef::to(uri: 'uri', cid: 'cid');

        $response = Bluesky::login('id', 'pass')->like($subject);

        $this->assertTrue($response->successful());
    }

    public function test_repost()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([]);

        $repost = Repost::create(StrongRef::to(uri: 'uri', cid: 'cid'));

        $response = Bluesky::login('id', 'pass')->repost($repost);

        $this->assertTrue($response->successful());
    }

    public function test_repost_subject()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([]);

        $subject = StrongRef::to(uri: 'uri', cid: 'cid');

        $response = Bluesky::login('id', 'pass')->repost($subject);

        $this->assertTrue($response->successful());
    }

    public function test_block()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push([]);

        $block = Block::create(did: 'did');

        $this->assertSame('did', $block->toArray()['subject']);
    }

    public function test_user_list()
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

    public function test_upsert_profile()
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

    public function test_search_posts()
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

    public function test_video_upload()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['token' => 'test'])
            ->push([]);

        $response = Bluesky::login('id', 'pass')
            ->uploadVideo(data: '', type: 'video/mp4');

        $this->assertTrue($response->successful());
    }

    public function test_video_status()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['token' => 'test'])
            ->push([]);

        $response = Bluesky::login('id', 'pass')
            ->getJobStatus(jobId: 'id');

        $this->assertTrue($response->successful());
    }

    public function test_video_limits()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->push(['token' => 'test'])
            ->push([]);

        $response = Bluesky::login('id', 'pass')
            ->getUploadLimits();

        $this->assertTrue($response->successful());
    }
}
