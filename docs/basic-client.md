Basic Client
====

## Authentication

Bluesky has two authentication methods: "App password" and "OAuth". "OAuth" is recommended from now on, so please also read the [Socialite](./socialite.md) docs.

### App password(Legacy)

You can easily log in with the identifier and password you set in .env.

```
// .env

BLUESKY_IDENTIFIER=
BLUESKY_APP_PASSWORD=
```

```php
use Revolution\Bluesky\Facades\Bluesky;

$profile = Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'))
                  ->getProfile()
                  ->json();
```

### OAuth

Specify the `OAuthSession` containing the token obtained from Socialite.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;

$session = OAuthSession::create(session('bluesky_session'));

$timeline = Bluesky::withToken($session)->getTimeline()->json();
```

## Response

The API results are returned as an `Illuminate\Http\Client\Response` object, so you can use it freely just like you would with normal Laravel.

```php
/** @var \Illuminate\Http\Client\Response $response */
$response->json();
$response->collect();
```

## Structure

- Bluesky Facade: Entrance
- Agent: Authentication
- Client: Send API request

Basic functions have a "ShortHand", so you can use it in three steps: Facade - Authentication - Send.

```php
use Revolution\Bluesky\Facades\Bluesky;

$response = Bluesky::withToken()->post();
```

Functions not in ShortHand can be executed via Client.

```php
use Revolution\Bluesky\Facades\Bluesky;

$response = Bluesky::withToken()->client(auth: true)->createRecord();
```

Finally, if you want to use an API not in Client, you can send anything with `send()`.

```php
use Revolution\Bluesky\Facades\Bluesky;

$response = Bluesky::withToken()->send(api: 'com.atproto.repo.createRecord', method: 'post', auth: true, params: []);
```

## Viewing my feed

Only my posts and reposts.

```php
use Revolution\Bluesky\Facades\Bluesky;

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->getAuthorFeed();

dump($response->collect('feed'));
```

## Viewing my timeline

```php
use Revolution\Bluesky\Facades\Bluesky;

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->getTimeline();

dump($response->json());
```

## Creating a post

```php
use Revolution\Bluesky\Facades\Bluesky;

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->post('test');

dump($response->json());
```

### TextBuilder

You can use `BlueskyMessage` class from [Notifications](./notification.md) as a text builder.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Notifications\BlueskyMessage;

$message = BlueskyMessage::create(text: 'test')
                          ->newLine()
                          ->link(text: 'http://', link: 'http://')
                          ->newLine()
                          ->tag(text: '#Laravel', tag: 'Laravel');

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->post($message);

dump($response->json());
```

### Social Card

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Notifications\BlueskyMessage;
use Revolution\Bluesky\Embed\External;

$external = External::create(title: 'Title', description: 'test', uri: 'http://');

$message = BlueskyMessage::create(text: 'test')
                          ->embed($external);

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->post($message);

dump($response->json());
```

### Upload Images

You can upload up to 4 images at a time.

```php
use Illuminate\Support\Facades\Storage;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Notifications\BlueskyMessage;
use Revolution\Bluesky\Embed\Images;

Bluesky::withToken();

$images = Images::create()
                ->add(alt: 'ALT TEXT', blob: function (): array {
                    return Bluesky::uploadBlob(Storage::get('test.png'), Storage::mimeType('test.png'))->json('blob');
                   })
                ->add(alt: 'image 2', blob: []);

$message = BlueskyMessage::create(text: 'test')
                          ->embed($images);

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::post($message);

dump($response->json());
```

## Public API

In fact, many of Bluesky's APIs can be used without authentication.

```php
use Revolution\Bluesky\Facades\Bluesky;

$profile = Bluesky::getProfile(actor: 'did')->json();

$feed = Bluesky::feed(actor: 'did')->json('feed');
```

## Macroable

```php
// AppServiceProvider

use Revolution\Bluesky\Facades\Bluesky;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Feed;
use Illuminate\Http\Client\Response;

    public function boot(): void
    {
        Bluesky::macro('searchPosts', function (string $q): Response {
            /** @var Bluesky $this */
            return $this->send(
                        api: Feed::searchPosts, 
                        method: 'get',
                        auth: false, 
                        params: [
                            'q' => $q,
                        ]
                   );
        });
    }
```

```php
use Revolution\Bluesky\Facades\Bluesky;

$posts = Bluesky::searchPosts('q')->json('posts');
```
