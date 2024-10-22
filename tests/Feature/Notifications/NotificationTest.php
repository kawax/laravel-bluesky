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
use Revolution\Bluesky\Enums\AtProto;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Notifications\BlueskyChannel;
use Revolution\Bluesky\Notifications\BlueskyMessage;
use Revolution\Bluesky\Notifications\BlueskyRoute;
use Revolution\Bluesky\Session\OAuthSession;
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
        $m = new BlueskyMessage(text: 'test');
        $m2 = BlueskyMessage::create(text: 'test');

        $this->assertIsArray($m->toArray());
        $this->assertSame('test', $m->toArray()['text']);
        $this->assertSame('test', $m2->toArray()['text']);
    }

    public function test_message_facets()
    {
        $m = BlueskyMessage::create(text: 'test')
            ->text('text')
            ->mention('at', 'did:')
            ->link('link', 'http://')
            ->tag('tag', 'tag');

        $this->assertIsArray($m->toArray());
        $this->assertSame('testtextatlinktag', $m->toArray()['text']);
        $this->assertIsArray($m->toArray()['facets']);
    }

    public function test_message_facet_index()
    {
        $m = BlueskyMessage::create(text: 'test')
            ->link('テスト', 'http://');

        $this->assertSame([
            'byteStart' => 4,
            'byteEnd' => 13,
        ], $m->toArray()['facets'][0]['index']);
    }

    public function test_message_facet()
    {
        $m = BlueskyMessage::create(text: 'test')
            ->facet([]);

        $this->assertIsArray($m->toArray()['facets']);
    }

    public function test_message_embed()
    {
        $m = BlueskyMessage::create(text: 'test')
            ->embed([]);

        $this->assertIsArray($m->toArray()['embed']);
    }

    public function test_message_embed_external()
    {
        $e = External::create(title: 'test', description: 'test', uri: 'http://');

        $m = BlueskyMessage::create(text: 'test')
            ->embed($e);

        $this->assertIsArray($m->toArray()['embed']);
        $this->assertSame('test', $m->toArray()['embed']['external']['title']);
        $this->assertSame('http://', $m->toArray()['embed']['external']['uri']);
        $this->assertSame(AtProto::External->value, $m->toArray()['embed']['$type']);
    }

    public function test_message_embed_images()
    {
        $images = Images::create()
            ->add(alt: 'alt', blob: ['blob'])
            ->add('alt2', fn () => ['blob2']);

        $m = BlueskyMessage::create(text: 'test')
            ->embed($images);

        $this->assertIsArray($m->toArray()['embed']);
        $this->assertSame('alt', $m->toArray()['embed']['images'][0]['alt']);
        $this->assertSame('alt2', $m->toArray()['embed']['images'][1]['alt']);
        $this->assertSame(['blob2'], $m->toArray()['embed']['images'][1]['image']);
        $this->assertSame(AtProto::Images->value, $m->toArray()['embed']['$type']);
    }

    public function test_message_langs()
    {
        $m = BlueskyMessage::create(text: 'test')
            ->langs(['en']);

        $this->assertSame(['en'], $m->toArray()['langs']);
    }

    public function test_message_new_line()
    {
        $m = BlueskyMessage::create(text: 'test')
            ->newLine(2)
            ->text('test');

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
            'iss' => 'iss',
        ]);

        $route = new BlueskyRoute(oauth: $session);

        $this->assertSame('iss', $route->oauth->issuer());
    }

    public function test_user_notify_oauth()
    {
        Bluesky::shouldReceive('withToken')->once()->andReturnSelf();
        Bluesky::shouldReceive('refreshSession')->once()->andReturnSelf();
        Bluesky::shouldReceive('post')->once();

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

    public function toBluesky(object $notifiable): BlueskyMessage
    {
        return BlueskyMessage::create(text: $this->text);
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
