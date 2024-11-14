Laravel Bluesky(AT Protocol)
====

**Work in progress**

## Requirements
- PHP >= 8.2
- Laravel >= 11.0

## Installation

```shell
composer require revolution/laravel-bluesky
```

### Uninstall
```shell
composer remove revolution/laravel-bluesky
```

## Quick start

### Search posts (no auth required, no need for your own account)

There are many public APIs that do not require authentication if you just want to retrieve data.

```php
// routes/web.php

use Illuminate\Support\Facades\Route;
use Revolution\Bluesky\Facades\Bluesky;

Route::get('search', function () {
    /** @var \Illuminate\Http\Client\Response $response */
    $response = Bluesky::searchPosts(q: '#bluesky', limit: 10);

    $response->collect('posts')
        ->each(function (array $post) {
            dump(data_get($post, 'author.displayName'));
            dump(data_get($post, 'author.handle'));
            dump(data_get($post, 'author.did'));
            dump(data_get($post, 'record.text'));
        });
});
```

### Get someone's posts (no auth required)

You can get your own posts by specifying your did or handle as the actor. No authentication is required to get and save your own posts.

```php
// routes/web.php

use Illuminate\Support\Facades\Route;
use Revolution\Bluesky\Facades\Bluesky;

Route::get('feed', function () {
    // "actor" is did(did:plc:***) or handle(***.bsky.social, alice.test)
    $response = Bluesky::getAuthorFeed(actor: 'alice.test');

    $response->collect('feed')
        ->each(function (array $feed) {
            dump(data_get($feed, 'post.author.displayName'));
            dump(data_get($feed, 'post.record.text'));
        });
});
```

### Create a post (requires auth)

There are two authentication methods for Bluesky: "App password" and "OAuth". Here we will use "App password". Obtain the App password from Bluesky and set it in .env.

```
// .env

BLUESKY_IDENTIFIER=***.bsky.social
BLUESKY_APP_PASSWORD=****-****-****-****
```

```php
// routes/web.php

use Illuminate\Support\Facades\Route;
use Revolution\Bluesky\Facades\Bluesky;

Route::get('post', function () {
    $response = Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'))
                       ->post('Hello Bluesky');
});
```

This is easy if you're just sending simple text, but in the real world you'll need to use `TextBuilder` to make links and tags work.

```php
// routes/web.php

use Illuminate\Support\Facades\Route;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\RichText\TextBuilder;

Route::get('text-builder', function () {
    $post = Post::build(function (TextBuilder $builder) {
        return $builder->text(text: 'Hello Bluesky')
                       ->newLine(count: 2)
                       ->link(text: 'https://bsky.app/', uri: 'https://bsky.app/')
                       ->newLine()
                       ->tag(text: '#Bluesky', tag: 'Bluesky')
    });

    $response = Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'))
                       ->post($post);
});
```

Following message will be posted:

```
Hello Bluesky

https://bsky.app/
#Bluesky
```

## Usage
- [Basic Client](./docs/basic-client.md)
- [Laravel Notifications](./docs/notification.md)
- [Socialite](./docs/socialite.md)

## Contracts
https://github.com/kawax/atproto-lexicon-contracts

## LICENCE
MIT
