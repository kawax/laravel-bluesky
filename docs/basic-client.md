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

Resume LegacySession.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\LegacySession;

Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'));

cache()->forever('bluesky_legacy_session', Bluesky::agent()->session()->toArray());

Bluesky::withToken(LegacySession::create(cache('bluesky_legacy_session', [])));

if(! Bluesky::check(){
    Bluesky::refreshSession();
}
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
use Illuminate\Http\Client\PendingRequest;

$response = Bluesky::withToken()
                   ->send(
                         api: 'com.atproto.repo.createRecord',
                         method: 'post',
                         auth: true,
                         params: [],
                         callback: function (PendingRequest $http) {
                                $http->...;
                         },
                   );
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
                      ->mention('@***.bsky.social')
                      ->newLine()
                      ->link('https://')
                      ->newLine()
                      ->tag('#Laravel');

$post = Post::create(text: $builder->text, facets: $builder->facets);

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
                   ->link('https://')
                   ->newLine()
                   ->tag('#Laravel')
                   ->toPost();

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->post($post);

dump($response->json());
```

Alternatively you can use `Post::build()`, use whichever you prefer.

```php
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\RichText\TextBuilder;

$post = Post::build(function (TextBuilder $builder) {
            $builder->text('test')
                    ->newLine()
                    ->link('https://')
                    ->newLine()
                    ->tag('#Laravel')
    });
```

### Auto-detect

TextBuilder also has `detectFacets()`, but it is not clear whether it works perfectly,
so it is safer to assemble it manually.

```php
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\RichText\TextBuilder;

$post = Post::build(function (TextBuilder $builder) {
            $builder->text('@alice.test')
                    ->newLine()
                    ->text('test')
                    ->newLine()
                    ->text('https://alice.test')
                    ->newLine()
                    ->text('#alice #🙃 #ゑ')
                    ->detectFacets();
    });
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

### External link / Social Card

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

### Upload video

There is no official documentation so this may change in the future.

```php
// routes/web.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\Embed\Video;

Route::post('upload_video', function (Request $request) {
    $upload = Bluesky::withToken()
                     ->uploadVideo(
                         data: $request->file('video')->get(),
                         type: $request->file('video')->getMimeType(),
                     );

    // If the upload doesn't work, check the error message.
    info('upload', $upload->json());
    // successful
    // ['did' => 'did:plc:***', 'jobId' => '***', 'status' => 'JOB_STATE_CREATED']
    // fails
    // ['did' => '', 'error' => '***', 'jobId' => '', 'message' => '***', 'status' => '']

    $jobId = $upload->json('jobId');

    // Bluesky::uploadVideo() returns a jobId, then you can use Bluesky::getJobStatus() to check if the upload is complete and retrieve the blob.

    $status = Bluesky::getJobStatus($jobId);

    info('status', $status->json());

    // Wait until state becomes JOB_STATE_COMPLETED.
    if($status->json('jobStatus.state') === 'JOB_STATE_COMPLETED') {
         $blob = $status->json('jobStatus.blob');
    }

    $video = Video::create(video: $blob);

    $post = Post::create(text: 'Upload video')->embed($video);

    $response = Bluesky::post($post);

    dump($response->json());
})
```

uploadVideo() also accepts StreamInterface.

```php
// UploadedFile

use GuzzleHttp\Psr7\Utils;

$upload = Bluesky::withToken()
                 ->uploadVideo(
                     data: Utils::streamFor(Utils::tryFopen($request->file('video')->getPathname(), 'rb')),
                     type: $request->file('video')->getMimeType(),
                 );
```

```php
// Upload from Storage

use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Psr7\Utils;

$upload = Bluesky::withToken()
                 ->uploadVideo(
                     data: Utils::streamFor(Storage::readStream('video.mp4')),
                     type: Storage::mimeType('video.mp4'),
                 );
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

### Quote post with media

Supported media: One of `Images` `Video` `External`

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\Embed\External;
use Revolution\Bluesky\Embed\QuoteRecordWithMedia;
use Revolution\Bluesky\Types\StrongRef;

$external = External::create(
    title: 'Title', 
    description: '', 
    uri: 'https://', 
);

$quote = QuoteRecordWithMedia::create(StrongRef::to(uri: 'at://', cid: 'cid'), media: $external);

$post = Post::create(text: 'test')
            ->embed($quote);

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::withToken()->post($post);

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
$response = Bluesky::withToken()->upsertProfile(function(Profile $profile) {
    $profile->displayName('new name')
            ->description('new description');

    $profile->avatar(function(): array {
        return Bluesky::uploadBlob(Storage::get('test.png'), Storage::mimeType('test.png'))->json('blob');
    });

    $profile->pinnedPost(StrongRef::to(uri: 'at://', cid: ''));
})

dump($response->json());
```

## Public API

In fact, many of Bluesky's APIs can be used without authentication.

```php
use Revolution\Bluesky\Facades\Bluesky;

$profile = Bluesky::getProfile(actor: 'did')->json();

$feed = Bluesky::getAuthorFeed(actor: 'did')->json('feed');
```

The request content will automatically change depending on whether you are authenticated or not. To explicitly use the
public API, use `logout()`.

```php
use Revolution\Bluesky\Facades\Bluesky;

// public requests
$profile = Bluesky::getProfile(actor: 'did')->json();

// authed requests
Bluesky::login();
$profile = Bluesky::getProfile(actor: 'did')->json();
// The DID of the authed user is set automatically.
$profile = Bluesky::getProfile()->json();

// public requests
Bluesky::logout();
$profile = Bluesky::getProfile(actor: 'did')->json();
```

There are some APIs whose responses change slightly depending on the authentication state.

```php
use Revolution\Bluesky\Facades\Bluesky;

// authed
$profile = Bluesky::login()->getProfile(actor: 'did')->json();
// has "viewer"
dump($profile['viewer']);

// public
$profile = Bluesky::logout()->getProfile(actor: 'did')->json();
// no "viewer"
dump($profile['viewer']);
```

## Macroable

```php
// AppServiceProvider

use Revolution\Bluesky\Facades\Bluesky;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Feed;
use Illuminate\Http\Client\Response;

    public function boot(): void
    {
        Bluesky::macro('timeline', function (?int $limit = 50, ?string $cursor = null): array {
            /** @var Bluesky $this */
            return $this->getTimeline(limit: $limit, cursor: $cursor)->throw()->json('feed');
        });
    }
```

```php
use Revolution\Bluesky\Facades\Bluesky;

$feed = Bluesky::timeline();
```
