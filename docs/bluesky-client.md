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

dump($response->json());
```

## Viewing my timeline
```php
use Revolution\Bluesky\Facades\Bluesky;

/** @var \Illuminate\Http\Client\Response $response */
$response = Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'))
       ->timeline()
       ->throw();

dump($response->collect());
```

## Creating a post

```php
use Revolution\Bluesky\Facades\Bluesky;

/** @var \Illuminate\Http\Client\Response $response */
Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'))
       ->post('test');

dump($response);
```
