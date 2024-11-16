Laravel Notifications
====

## Available Channels
- `BlueskyChannel`: Notify as a normal published post.
- `BlueskyPrivateChannel`: Notify to the receiver as a private chat/DM.

## Notification class

### BlueskyChannel

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
        $external = External::create(title: 'Title', description: 'test', uri: 'https://');

        return Post::build(function(TextBuilder $builder) {
            return $builder->text(text: 'test')
                           ->newLine()
                           ->tag(text: '#Laravel', tag: 'Laravel');
        })->embed($external);
    }
}
```

The usage of `Post` is the same as BasicClient, so TextBuilder, Embed, etc. can be used in the same way.

### BlueskyPrivateChannel

`BlueskyPrivateMessage` is almost the same as `Post`, but only supports text, facets, and embed. Embed only supports `QuoteRecord`.

```php
use Illuminate\Notifications\Notification;
use Revolution\Bluesky\Notifications\BlueskyPrivateChannel;
use Revolution\Bluesky\Notifications\BlueskyPrivateMessage;
use Revolution\Bluesky\RichText\TextBuilder;
use Revolution\Bluesky\Embed\QuoteRecord;

class TestNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return [
            BlueskyPrivateChannel::class
        ];
    }

    public function toBlueskyPrivate(object $notifiable): BlueskyPrivateMessage
    {
        $quote = QuoteRecord::create(StrongRef::to(uri: 'at://', cid: 'cid'));

        return BlueskyPrivateMessage::build(function(TextBuilder $builder) {
            return $builder->text(text: 'test')
                           ->newLine()
                           ->tag(text: '#Laravel', tag: 'Laravel');
        })->embed($quote);
    }
}
```

## On-Demand Notifications

### BlueskyChannel

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

### BlueskyPrivateChannel

In PrivateChannel, you must specify a `receiver`, and receiver user must be set up to receive DMs.

App password requires privileges to send DMs.

```php
use Illuminate\Support\Facades\Notification;
use Revolution\Bluesky\Notifications\BlueskyRoute;
use Revolution\Bluesky\Session\OAuthSession;
use App\Models\User;

// App password
Notification::route('bluesky-private', BlueskyRoute::to(identifier: config('bluesky.identifier'), password: config('bluesky.password'), receiver: 'did'))
            ->notify(new TestNotification());

// OAuth
$user = User::find(1);
$session = OAuthSession::create([
    'did' => $user->did,
    'iss' => $user->iss,
    'refresh_token' => $user->refresh_token,
]);
Notification::route('bluesky-private', BlueskyRoute::to(oauth: $session, receiver: 'did'))
            ->notify(new TestNotification());
```

Since you cannot send a DM to yourself in Bluesky, if you want to set yourself as the receiver, you need a separate account for the sender.

```php
// Send to yourself a private message

use Illuminate\Support\Facades\Notification;
use Revolution\Bluesky\Notifications\BlueskyRoute;
use Revolution\Bluesky\Session\OAuthSession;

Notification::route('bluesky-private', BlueskyRoute::to(identifier: 'sender identifier', password: 'sender password', receiver: 'your did'))
            ->notify(new TestNotification());
```

## User Notifications

### BlueskyChannel

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

### BlueskyPrivateChannel

```php
use Illuminate\Notifications\Notifiable;
use Revolution\Bluesky\Notifications\BlueskyRoute;
use Revolution\Bluesky\Session\OAuthSession;

class User
{
    use Notifiable;

    public function routeNotificationForBlueskyPrivate($notification): BlueskyRoute
    {
        // App password
        return BlueskyRoute::to(identifier: $this->bluesky_identifier, password: $this->bluesky_password, receiver: $this->receiver);

        // OAuth
        $session = OAuthSession::create([
            'did' => $this->did,
            'iss' => $this->iss,
            'refresh_token' => $this->refresh_token,
        ]);
        return BlueskyRoute::to(oauth: $session, receiver: $this->receiver);
    }
}
```

```php
$user->notify(new TestNotification());
```

You can also specify a user as a receiver.

```php
    public function routeNotificationForBlueskyPrivate($notification): BlueskyRoute
    {
        // App password
        return BlueskyRoute::to(identifier: 'sender identifier', password: 'sender password', receiver: $this->did);
    }
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
