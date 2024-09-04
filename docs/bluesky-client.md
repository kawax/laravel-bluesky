BlueskyClient
====

Basic client.

## Viewing my feed
Only my posts and reposts.

```php
use Revolution\Bluesky\Facades\Bluesky;

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'))
       ->feed();

dump($response->collect('feed'));
```

## Viewing my timeline
```php
use Revolution\Bluesky\Facades\Bluesky;

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'))
       ->timeline()
       ->throw();

dump($response->json());
```

## Creating a post

```php
use Revolution\Bluesky\Facades\Bluesky;

/** @var \Illuminate\Http\Client\Response $response */
Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'))
       ->post('test');

dump($response);
```

### TextBuilder
You can use `BlueskyMessage` class from [Laravel Notifications](./notification.md) as a text builder.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Notifications\BlueskyMessage;

$message = BlueskyMessage::create(text: 'test')
                          ->newLine()
                          ->link(text: 'http://', link: 'http://')
                          ->newLine()
                          ->tag(text: '#Laravel', tag: 'Laravel');

/** @var \Illuminate\Http\Client\Response $response */
Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'))
       ->post(text: $message->text, facets: $message->facets);

dump($response);
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
Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'))
       ->post(text: $message->text, embed: $message->embed);

dump($response);
```

## Login
```php
use Revolution\Bluesky\Facades\Bluesky;

Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'));

dump(Bluesky::check());
// true
```

## Logout
```php
use Revolution\Bluesky\Facades\Bluesky;

Bluesky::logout();

dump(Bluesky::check());
// false
```
