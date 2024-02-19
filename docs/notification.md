Laravel Notifications
====

## Notification class
```php
use Illuminate\Notifications\Notification;
use Revolution\Bluesky\Notifications\BlueskyChannel;
use Revolution\Bluesky\Notifications\BlueskyMessage;

class TestNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return [
            BlueskyChannel::class
        ];
    }

    public function toBluesky(object $notifiable): BlueskyMessage
    {
        return BlueskyMessage::create(text: 'test');
    }
}
```

## On-Demand Notifications
```php
use Illuminate\Support\Facades\Notification;
use Revolution\Bluesky\Notifications\BlueskyRoute;

Notification::route('bluesky', BlueskyRoute::to(identifier: config('bluesky.identifier'), password: config('bluesky.password')))
            ->notify(new TestNotification());
```

## User Notifications
```php
use Illuminate\Notifications\Notifiable;
use Revolution\Bluesky\Notifications\BlueskyRoute;

class User
{
    use Notifiable;

    public function routeNotificationForBluesky($notification): BlueskyRoute
    {
        return BlueskyRoute::to(identifier: $this->bluesky_identifier, password: $this->bluesky_password);
    }
}
```

```php
$user->notify(new TestNotification());
```
