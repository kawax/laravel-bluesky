<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications\Private;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\RequestException;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Revolution\Bluesky\Embed\QuoteRecord;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\AtProto\Lexicon\Enum\Embed;
use Revolution\Bluesky\Notifications\BlueskyPrivateChannel;
use Revolution\Bluesky\Notifications\BlueskyPrivateMessage;
use Revolution\Bluesky\Notifications\BlueskyRoute;
use Revolution\Bluesky\RichText\TextBuilder;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Types\StrongRef;
use Tests\TestCase;

class NotificationPrivateTest extends TestCase
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
            ->push(['convo' => ['id' => 'id']])
            ->whenEmpty(Http::response());

        Notification::route('bluesky-private', BlueskyRoute::to(identifier: 'identifier', password: 'password', receiver: 'did'))
            ->notify(new TestNotification(text: 'test'));

        Http::assertSentCount(3);

        $recorded = Http::recorded();

        $this->assertSame('identifier', $recorded[0][0]['identifier']);
        $this->assertSame(['did'], $recorded[1][0]['members']);
        $this->assertSame('id', $recorded[2][0]['convoId']);
    }

    public function test_notification_failed()
    {
        $this->expectException(RequestException::class);

        Http::fakeSequence()
            ->push($this->session)
            ->push(['convo' => ['id' => 'id']])
            ->whenEmpty(Http::response('', 500));

        Notification::route('bluesky-private', BlueskyRoute::to(identifier: 'identifier', password: 'password', receiver: 'did'))
            ->notify(new TestNotification(text: 'test'));

        Http::assertSentCount(3);
    }

    public function test_notification_fake()
    {
        Notification::fake();

        Notification::route('bluesky-private', BlueskyRoute::to(identifier: 'identifier', password: 'password', receiver: 'did'))
            ->notify(new TestNotification(text: 'test'));

        Notification::assertSentOnDemand(TestNotification::class);
    }

    public function test_message()
    {
        $m = new BlueskyPrivateMessage(text: 'test');
        $m2 = BlueskyPrivateMessage::create(text: 'test')->text('test2');

        $this->assertIsArray($m->toArray());
        $this->assertSame('test', $m->toArray()['text']);
        $this->assertSame('test2', $m2->toArray()['text']);
        $this->assertArrayNotHasKey('facets', $m->toArray());
        $this->assertArrayNotHasKey('embed', $m->toArray());
    }

    public function test_message_facets()
    {
        $builder = TextBuilder::make('test')
            ->text(text: 'text')
            ->mention(text: 'at', did: 'did:')
            ->link(text: 'link', uri: 'http://')
            ->tag(text: 'tag', tag: 'tag')
            ->toArray();

        $m = BlueskyPrivateMessage::create(text: $builder['text'], facets: $builder['facets']);

        $this->assertIsArray($m->toArray());
        $this->assertSame('testtextatlinktag', $m->toArray()['text']);
        $this->assertIsArray($m->toArray()['facets']);
    }

    public function test_message_facet_index()
    {
        $builder = TextBuilder::make('test')
            ->link('テスト', 'http://');

        $m = BlueskyPrivateMessage::create(text: $builder->text)
            ->facets($builder->facets);

        $this->assertSame([
            'byteStart' => 4,
            'byteEnd' => 13,
        ], $m->toArray()['facets'][0]['index']);
    }

    public function test_message_facet()
    {
        $m = BlueskyPrivateMessage::create(text: 'test')
            ->facets([]);

        $this->assertIsArray($m->toArray()['facets']);
    }

    public function test_message_embed()
    {
        $m = BlueskyPrivateMessage::create(text: 'test')
            ->embed([
                '$type' => Embed::External->value,
            ]);

        $this->assertIsArray($m->toArray()['embed']);
    }

    public function test_message_embed_quote()
    {
        $quote = QuoteRecord::create(StrongRef::to(uri: 'uri', cid: 'cid'));

        $m = BlueskyPrivateMessage::create(text: 'test')
            ->embed($quote);

        $this->assertIsArray($m->toArray()['embed']);
        $this->assertSame('uri', $m->toArray()['embed']['record']['uri']);
        $this->assertSame(Embed::Record->value, $m->toArray()['embed']['$type']);
    }

    public function test_user_notify()
    {
        Http::fake(fn () => $this->session);

        $user = new TestUser();

        $user->notify(new TestNotification(text: 'test'));

        Http::assertSentCount(3);
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

        Bluesky::shouldReceive('withToken->refreshSession->client->chat->getConvoForMembers->json')->once();
        Bluesky::shouldReceive('client->chat->sendMessage->throw')->once();

        $user = new TestUserOAuth();

        $user->notify(new TestNotification(text: 'test'));

        Http::assertSentCount(0);
    }

    public function test_private_message_build()
    {
        $m = BlueskyPrivateMessage::build(function (TextBuilder $builder) {
            $builder->text('test');
        });

        $this->assertSame('test', $m->toArray()['text']);
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
        return [BlueskyPrivateChannel::class];
    }

    public function toBlueskyPrivate(object $notifiable): BlueskyPrivateMessage
    {
        return BlueskyPrivateMessage::create(text: $this->text);
    }
}

class TestUser extends Model
{
    use Notifiable;

    public function routeNotificationForBlueskyPrivate($notification): BlueskyRoute
    {
        return BlueskyRoute::to(identifier: 'identifier', password: 'password', receiver: 'did');
    }
}

class TestUserOAuth extends Model
{
    use Notifiable;

    public function routeNotificationForBlueskyPrivate($notification): BlueskyRoute
    {
        $session = OAuthSession::create([
            'refresh_token' => 'refresh_token',
            'iss' => 'iss',
        ]);

        return BlueskyRoute::to(oauth: $session, receiver: 'did');
    }
}
