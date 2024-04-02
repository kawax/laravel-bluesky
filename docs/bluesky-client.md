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
