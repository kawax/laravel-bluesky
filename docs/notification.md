Laravel Notifications
====

## Notification class
```php
use Illuminate\Notifications\Notification;
use Revolution\Bluesky\Notifications\BlueskyChannel;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\RichText\TextBuilder;
use Revolution\Bluesky\Embed\External;

class TestNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return [
            BlueskyChannel::class
        ];
    }

    public function toBluesky(object $notifiable): Post
    {
        $external = External::create(title: 'Title', description: 'test', uri: 'http://');

        $post = TextBuilder::make(text: 'test')
                           ->newLine()
                           ->tag(text: '#Laravel', tag: 'Laravel')
                           ->toPost();

        return $post->embed($external);
    }
}
```

Bluesky is not automatically linked, so you need to specify `facets` to link it.  
https://docs.bsky.app/docs/advanced-guides/post-richtext

## On-Demand Notifications
```php
use Illuminate\Support\Facades\Notification;
use Revolution\Bluesky\Notifications\BlueskyRoute;
use Revolution\Bluesky\Session\OAuthSession;
use App\Models\User;

// App password
Notification::route('bluesky', BlueskyRoute::to(identifier: config('bluesky.identifier'), password: config('bluesky.password')))
            ->notify(new TestNotification());

// OAuth
$user = User::find(1);
$session = OAuthSession::create([
    'did' => $user->did,
    'iss' => $user->iss,
    'refresh_token' => $user->refresh_token,
]);
Notification::route('bluesky', BlueskyRoute::to(oauth: $session))
            ->notify(new TestNotification());
```

## User Notifications
```php
use Illuminate\Notifications\Notifiable;
use Revolution\Bluesky\Notifications\BlueskyRoute;
use Revolution\Bluesky\Session\OAuthSession;

class User
{
    use Notifiable;

    public function routeNotificationForBluesky($notification): BlueskyRoute
    {
        // App password
        return BlueskyRoute::to(identifier: $this->bluesky_identifier, password: $this->bluesky_password);

        // OAuth
        $session = OAuthSession::create([
            'did' => $this->did,
            'iss' => $this->iss,
            'refresh_token' => $this->refresh_token,
        ]);
        return BlueskyRoute::to(oauth: $session);
    }
}
```

```php
$user->notify(new TestNotification());
```

## BlueskyRoute

The method of specification differs depending on the authentication method, either "App password" or "OAuth". It is recommended to always use named arguments.

```php
use Revolution\Bluesky\Notifications\BlueskyRoute;
use Revolution\Bluesky\Session\OAuthSession;

// App password
BlueskyRoute::to(identifier: config('bluesky.identifier'), password: config('bluesky.password'))

// OAuth
$session = OAuthSession::create([
    'did' => '...',
    'iss' => '...',
    'refresh_token' => '...',
]);
BlueskyRoute::to(oauth: $session);
```

If you just want to notify your account, **App password** is a better option.
You don't need to worry about updating refresh_token, and you can use it just by setting it in `.env`.

```
// .env

BLUESKY_IDENTIFIER=
BLUESKY_APP_PASSWORD=
```
