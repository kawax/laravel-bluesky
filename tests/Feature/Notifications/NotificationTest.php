<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\RequestException;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Revolution\Bluesky\Embed\External;
use Revolution\Bluesky\Embed\Images;
use Revolution\Bluesky\Embed\QuoteRecord;
use Revolution\Bluesky\Embed\Video;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\AtProto\Lexicon\Enum\Embed;
use Revolution\Bluesky\Notifications\BlueskyChannel;
use Revolution\Bluesky\Notifications\BlueskyRoute;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\RichText\TextBuilder;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Types\Blob;
use Revolution\Bluesky\Types\StrongRef;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    protected array $session = ['accessJwt' => 'test', 'refreshJwt' => 'test', 'did' => 'test', 'handle' => 'handle'];

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

    public function test_notification()
    {
        Http::fakeSequence()
            ->push($this->session)
            ->whenEmpty(Http::response());

        Notification::route('bluesky', BlueskyRoute::to(identifier: 'identifier', password: 'password'))
            ->notify(new TestNotification(text: 'test'));

        Http::assertSentCount(2);

        $recorded = Http::recorded();

        $this->assertSame('identifier', $recorded[0][0]['identifier']);
        $this->assertSame('test', $recorded[1][0]['record']['text']);
    }

    public function test_notification_failed()
    {
        $this->expectException(RequestException::class);

        Http::fakeSequence()
            ->push($this->session)
            ->whenEmpty(Http::response('', 500));

        Notification::route('bluesky', BlueskyRoute::to(identifier: 'identifier', password: 'password'))
            ->notify(new TestNotification(text: 'test'));

        Http::assertSentCount(2);
    }

    public function test_notification_fake()
    {
        Notification::fake();

        Notification::route('bluesky', BlueskyRoute::to(identifier: 'identifier', password: 'password'))
            ->notify(new TestNotification(text: 'test'));

        Notification::assertSentOnDemand(TestNotification::class);
    }

    public function test_message()
    {
        $m = new Post(text: 'test');
        $m2 = Post::create(text: 'test');

        $this->assertIsArray($m->toArray());
        $this->assertSame('test', $m->toArray()['text']);
        $this->assertSame('test', $m2->toArray()['text']);
        $this->assertArrayNotHasKey('facets', $m->toArray());
        $this->assertArrayNotHasKey('embed', $m->toArray());
        $this->assertArrayNotHasKey('langs', $m->toArray());
    }

    public function test_message_facets()
    {
        $builder = TextBuilder::make('test')
            ->text(text: 'text')
            ->mention(text: 'at', did: 'did:')
            ->link(text: 'link', uri: 'http://')
            ->tag(text: 'tag', tag: 'tag')
            ->toArray();

        $m = Post::create(text: $builder['text'], facets: $builder['facets']);

        $this->assertIsArray($m->toArray());
        $this->assertSame('testtextatlinktag', $m->toArray()['text']);
        $this->assertIsArray($m->toArray()['facets']);
    }

    public function test_message_facet_index()
    {
        $builder = TextBuilder::make('test')
            ->link('テスト', 'http://')
            ->toArray();

        $m = Post::create(text: $builder['text'])
            ->facets($builder['facets']);

        $this->assertSame([
            'byteStart' => 4,
            'byteEnd' => 13,
        ], $m->toArray()['facets'][0]['index']);
    }

    public function test_message_facet()
    {
        $m = Post::create(text: 'test')
            ->facets([]);

        $this->assertIsArray($m->toArray()['facets']);
    }

    public function test_text_builder_to_post()
    {
        $post = TextBuilder::make('test')->toPost();

        $this->assertInstanceOf(Post::class, $post);
        $this->assertSame('test', $post->toArray()['text']);
    }

    public function test_message_embed()
    {
        $m = Post::create(text: 'test')
            ->embed([
                '$type' => Embed::External->value,
            ]);

        $this->assertIsArray($m->toArray()['embed']);
    }

    public function test_message_embed_external()
    {
        $e = External::create(title: 'test', description: 'test', uri: 'http://');

        $m = Post::create(text: 'test')
            ->embed($e);

        $this->assertIsArray($m->toArray()['embed']);
        $this->assertSame('test', $m->toArray()['embed']['external']['title']);
        $this->assertSame('http://', $m->toArray()['embed']['external']['uri']);
        $this->assertSame(Embed::External->value, $m->toArray()['embed']['$type']);
    }

    public function test_message_embed_images()
    {
        $blob2 = Blob::fromArray([
            'type' => 'blob',
            'ref' => [
                '$link' => '...',
            ],
            'mimeType' => 'image/jpeg',
            'size' => 1000,
        ]);

        $images = Images::create()
            ->add(alt: 'alt', blob: ['blob'])
            ->add('alt2', fn () => $blob2);

        $m = Post::create(text: 'test')
            ->embed($images);

        $this->assertIsArray($m->toArray()['embed']);
        $this->assertSame('alt', $m->toArray()['embed']['images'][0]['alt']);
        $this->assertSame('alt2', $m->toArray()['embed']['images'][1]['alt']);
        $this->assertSame($blob2->toArray(), $m->toArray()['embed']['images'][1]['image']);
        $this->assertSame(Embed::Images->value, $m->toArray()['embed']['$type']);
    }

    public function test_message_embed_video()
    {
        $video_blob = Blob::make(link: '...', mimeType: 'video/mp4', size: 10000);

        $v = Video::create(video: $video_blob, alt: 'alt', captions: [], aspectRatio: ['width' => 1, 'height' => 1]);

        $m = Post::create(text: 'test')
            ->embed($v);

        $this->assertIsArray($m->toArray()['embed']);
        $this->assertSame($video_blob->toArray(), $m->toArray()['embed']['video']['video']);
        $this->assertSame('alt', $m->toArray()['embed']['video']['alt']);
        $this->assertSame(Embed::Video->value, $m->toArray()['embed']['$type']);
    }

    public function test_message_embed_quote()
    {
        $quote = QuoteRecord::create(StrongRef::to(uri: 'uri', cid: 'cid'));

        $m = Post::create(text: 'test')
            ->embed($quote);

        $this->assertIsArray($m->toArray()['embed']);
        $this->assertSame('uri', $m->toArray()['embed']['record']['uri']);
        $this->assertSame(Embed::Record->value, $m->toArray()['embed']['$type']);
    }

    public function test_message_langs()
    {
        $m = Post::create(text: 'test')
            ->langs(['en']);

        $this->assertSame(['en'], $m->toArray()['langs']);
    }

    public function test_message_new_line()
    {
        $builder = TextBuilder::make('test')
            ->newLine(2)
            ->text('test')
            ->toArray();

        $m = Post::create(text: $builder['text'], facets: $builder['facets']);

        $this->assertSame('test'.PHP_EOL.PHP_EOL.'test', $m->toArray()['text']);
    }

    public function test_route()
    {
        $route = new BlueskyRoute(identifier: 'identifier', password: 'password');
        $route2 = BlueskyRoute::to(identifier: 'identifier', password: 'password');

        $this->assertSame('identifier', $route->identifier);
        $this->assertSame('identifier', $route2->identifier);
    }

    public function test_user_notify()
    {
        Http::fake(fn () => $this->session);

        $user = new TestUser();

        $user->notify(new TestNotification(text: 'test'));

        Http::assertSentCount(2);
    }

    public function test_route_oauth()
    {
        $session = OAuthSession::create([
            'refresh_token' => 'refresh_token',
            'iss' => 'https://iss',
        ]);

        $route = new BlueskyRoute(oauth: $session);

        $this->assertSame('https://iss', $route->oauth->issuer());
    }

    public function test_user_notify_oauth()
    {
        Http::fake();

        Bluesky::shouldReceive('withToken->refreshSession->post->throw')->once();

        $user = new TestUserOAuth();

        $user->notify(new TestNotification(text: 'test'));

        Http::assertSentCount(0);
    }
}

class TestNotification extends \Illuminate\Notifications\Notification
{
    public function __construct(
        protected string $text,
    ) {
    }

    public function via(object $notifiable): array
    {
        return [BlueskyChannel::class];
    }

    public function toBluesky(object $notifiable): Post
    {
        return Post::create(text: $this->text);
    }
}

class TestUser extends Model
{
    use Notifiable;

    public function routeNotificationForBluesky($notification): BlueskyRoute
    {
        return BlueskyRoute::to(identifier: 'identifier', password: 'password');
    }
}

class TestUserOAuth extends Model
{
    use Notifiable;

    public function routeNotificationForBluesky($notification): BlueskyRoute
    {
        $session = OAuthSession::create([
            'refresh_token' => 'refresh_token',
            'iss' => 'iss',
        ]);

        return BlueskyRoute::to(oauth: $session);
    }
}
