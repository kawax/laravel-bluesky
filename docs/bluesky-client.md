BlueskyClient
====

Basic client.

## Viewing my feed
Only my posts and reposts.

```php
use Revolution\Bluesky\Facades\Bluesky;

/** @var \Illuminate\Support\Collection $response */
$response = Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'))
       ->feed();

dump($response);
```

## Viewing my timeline
```php
use Revolution\Bluesky\Facades\Bluesky;

/** @var \Illuminate\Support\Collection $response */
$response = Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'))
       ->timeline();

dump($response);
```

## Creating a post

```php
use Revolution\Bluesky\Facades\Bluesky;

/** @var \Illuminate\Support\Collection $response */
Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'))
       ->post('test');

dump($response);
```
