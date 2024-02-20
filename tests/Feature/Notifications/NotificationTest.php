<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\RequestException;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Revolution\Bluesky\Notifications\BlueskyChannel;
use Revolution\Bluesky\Notifications\BlueskyMessage;
use Revolution\Bluesky\Notifications\BlueskyRoute;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    public function test_notification()
    {
        Http::fakeSequence()
            ->push(['accessJwt' => 'test', 'did' => 'test'])
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
            ->push(['accessJwt' => 'test', 'did' => 'test'])
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

        $this->assertIsArray($m->toArray());
    }

    public function test_route()
    {
        $route = new BlueskyRoute(identifier: 'identifier', password: 'password', service: 'https://');

        $this->assertSame('identifier', $route->identifier);
    }

    public function test_user_notify()
    {
        Http::fake();

        $user = new TestUser();

        $user->notify(new TestNotification(text: 'test'));

        Http::assertSentCount(2);
    }
}

class TestNotification extends \Illuminate\Notifications\Notification
{
    public function __construct(
        protected string $text,
    )
    {
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
