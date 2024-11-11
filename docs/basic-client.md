Basic Client
====

## Authentication

Bluesky has two authentication methods: "App password" and "OAuth". "OAuth" is recommended from now on,
so please also read the [Socialite](./socialite.md) docs.

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
                  ->getProfile();
```

### OAuth

Specify the `OAuthSession` containing the token obtained from Socialite.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;

$session = OAuthSession::create(session('bluesky_session'));

$timeline = Bluesky::withToken($session)->getTimeline();
```

## Response

The API results are returned as an `Illuminate\Http\Client\Response` object,
so you can use it freely just like you would with normal Laravel.

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

If it's just simple text you can just pass the string, but no automatic links or tags will work.

```php
use Revolution\Bluesky\Facades\Bluesky;

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->post('test');

dump($response->json());
```

### TextBuilder

Bluesky requires you to set up facets for links and tags to work. `TextBuilder` makes this easy.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\RichText\TextBuilder;

$builder = TextBuilder::make(text: 'test')
                      ->newLine()
                      ->link(text: 'http://', uri: 'http://')
                      ->newLine()
                      ->tag(text: '#Laravel', tag: 'Laravel')
                      ->toArray();

$post = Post::create(text: $builder['text'], facets: $builder['facets']);

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->post($post);

dump($response->json());
```

You can create a Post object directly using `toPost()`.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\RichText\TextBuilder;

$post = TextBuilder::make(text: 'test')
                   ->newLine()
                   ->link(text: 'http://', link: 'http://')
                   ->newLine()
                   ->tag(text: '#Laravel', tag: 'Laravel')
                   ->toPost();

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->post($post);

dump($response->json());
```

### Reply

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\Types\ReplyRef;
use Revolution\Bluesky\Types\StrongRef;

$reply = ReplyRef::to(root: StrongRef::to(uri: 'at://', cid: 'cid'), parent: StrongRef::to(uri: 'at://', cid: 'cid'));

$post = Post::create(text: 'test')
            ->reply($reply);

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->post($post);

dump($response->json());
```

### Social Card

```php
use Illuminate\Support\Facades\Storage;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\Embed\External;

$external = External::create(
    title: 'Title', 
    description: 'test', 
    uri: 'http://', 
    thumb: fn() => Bluesky::uploadBlob(Storage::get('test.png'), Storage::mimeType('test.png'))->json('blob'),
);

$post = Post::create(text: 'test')
            ->embed($external);

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->post($post);

dump($response->json());
```

### Quote post

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\Embed\QuoteRecord;
use Revolution\Bluesky\Types\StrongRef;

$quote = QuoteRecord::create(StrongRef::to(uri: 'at://', cid: 'cid'));

$post = Post::create(text: 'test')
            ->embed($quote);

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->post($post);

dump($response->json());
```

### Upload Images

Images are passed as an array of data called a blob object.

```json
{
    "$type": "blob",
    "ref": {
        "$link": "..."
    },
    "mimeType": "image/png",
    "size": 10000
}
```

You can upload up to 4 images at a time.

```php
use Illuminate\Support\Facades\Storage;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\Embed\Images;

Bluesky::withToken();

$images = Images::create()
                ->add(alt: 'ALT TEXT', blob: function (): array {
                    return Bluesky::uploadBlob(Storage::get('test.png'), Storage::mimeType('test.png'))->json('blob');
                   })
                ->add(alt: 'image 2', blob: []);

$post = Post::create(text: 'test')
            ->embed($images);

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::post($post);

dump($response->json());
```

## Following a user

```php
use Revolution\Bluesky\Facades\Bluesky;

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->follow(did: 'did:plc:...');

dump($response->json());
```

## Like

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Types\StrongRef;

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->like(StrongRef::to(uri: 'at://', cid: 'cid'));

dump($response->json());
```

## Repost

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Repost;
use Revolution\Bluesky\Types\StrongRef;

$repost = Repost::create(StrongRef::to(uri: 'at://', cid: 'cid'));

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->repost($repost);

dump($response->json());
```

## Editing profiles

Use a Closure to update an existing profile.

```php
use Illuminate\Support\Facades\Storage;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Profile;
use Revolution\Bluesky\Types\StrongRef;

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->upsertProfile(function(Profile $profile): Profile {
    $profile->displayName('new name')
            ->description('new description');

    $profile->avatar(function(): array {
        return Bluesky::uploadBlob(Storage::get('test.png'), Storage::mimeType('test.png'))->json('blob');
    });

    $profile->pinnedPost(StrongRef::to(uri: 'at://', cid: ''));

    return $profile;
})

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
