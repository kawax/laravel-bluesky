Feed Generator
====

Please also refer to the official starter kit.  
https://github.com/bluesky-social/feed-generator

Here is an example of the Feed Generator.  
https://bsky.app/profile/invokable.net/feed/artisan

## Register FeedGenerator algorithm

The simplest usage in Laravel is to just register an algorithm.

```php
// Register in your AppServiceProvider::boot()

use Illuminate\Http\Request;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\FeedGenerator\FeedGenerator;

FeedGenerator::register(name: 'artisan', algo: function(int $limit, ?string $cursor, ?string $user, Request $request): array {
    // The implementation is entirely up to you.

    $response = Bluesky::searchPosts(q: '#laravel', until: $cursor, limit: $limit);

    $cursor = data_get($response->collect('posts')->last(), 'indexedAt');

    $feed = $response->collect('posts')->map(function(array $post) {
        return ['post' => data_get($post, 'uri')];
    })->toArray();

    // You can also use the Request object to change the results depending on the user's state.
    info('user: '.$user);// Requesting user's DID. 'did:plc:***'
    info('header', $request->header());

    return compact('cursor', 'feed');
});
```

`name` should be an url-safe string.

Returns an array containing `cursor` and `feed`.

```php
[
    'cursor' => '',
    'feed' => [
       ['post' => 'at://'],
       ['post' => 'at://'],
    ],
]
```

All the routes we need are defined in the package.

- http://localhost/xrpc/app.bsky.feed.getFeedSkeleton?feed=at://did:web:example.com/app.bsky.feed.generator/artisan
- http://localhost/xrpc/app.bsky.feed.describeFeedGenerator
- http://localhost/.well-known/did.json
- Service did is set from the current URL `did:web:example.com`.

What you decide is the name and implementation of the FeedGenerator.

Up to this point, we have covered creating a FeedGenerator in Laravel.

We also need to publish the FeedGenerator on Bluesky.

## Create publish feed generator command

This is a case where each person creates their own commands.

```bash
php artisan make:command PublishGeneratorCommand
```

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Generator;

class PublishGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:publish-generator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $generator = Generator::create(did: 'did:web:example.com', displayName: 'Feed name')
                              ->description('Feed description');

        $res = Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'))
                      ->publishFeedGenerator(name: 'artisan', generator: $generator);

        dump($res->json());

        return 0;
    }
}
```

```bash
php artisan bluesky:publish-generator
```

If successful, the link will be added to the Feed on your Bluesky profile page, so you can check it.

You can run `publishFeedGenerator` any number of times, as it simply updates the information.

## Creating multiple FeedGenerators

You can create as many FeedGenerators as you want by changing the `name`.

```php
// AppServiceProvider::boot()

use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\FeedGenerator\FeedGenerator;

FeedGenerator::register(name: 'feed1', function() {});

FeedGenerator::register(name: 'feed2', function() {});
```

```php
// PublishGeneratorCommand

Bluesky::login(identifier: config('bluesky.identifier'), password: config('bluesky.password'));

$generator1 = Generator::create(did: 'did:web:example.com', displayName: 'Feed 1')
                       ->description('Feed 1');

$res = Bluesky::publishFeedGenerator(name: 'feed1', generator: $generator1);

$generator2 = Generator::create(did: 'did:web:example.com', displayName: 'Feed 2')
                       ->description('Feed 2');

$res = Bluesky::publishFeedGenerator(name: 'feed2', generator: $generator2);
```

## Separate algorithm class

Instead of using a Closure, you can also create an independent class.

Create a "callable" class in the location of your choice and register it with the AppServiceProvider.

```php
// Anywhere

namespace App\FeedGenerator;

use Illuminate\Http\Request;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Contracts\FeedGeneratorAlgorithm;

class ArtisanFeed implements FeedGeneratorAlgorithm
{
    public function __invoke(int $limit, ?string $cursor, ?string $user, Request $request): array
    {
        $response = Bluesky::searchPosts(q: '#laravel', until: $cursor, limit: $limit);

        $cursor = data_get($response->collect('posts')->last(), 'indexedAt');

        $feed = $response->collect('posts')->map(function (array $post) {
            return ['post' => data_get($post, 'uri')];
        })->toArray();

        info('user: '.$user);
        info('header', $request->header());

        return compact('cursor', 'feed');
    }
}
```

```php
// AppServiceProvider::boot()

use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\FeedGenerator\FeedGenerator;
use App\FeedGenerator\ArtisanFeed;

FeedGenerator::register(name: 'artisan', algo: ArtisanFeed::class);
```

## Authentication

The "Authentication" section of the official starter kit is enabled by default. To disable it, provide a closure in your AppServiceProvider that simply returns the user did.

```php
// AppServiceProvider::boot()

use Illuminate\Http\Request;
use Revolution\Bluesky\Crypto\JsonWebToken;
use Revolution\Bluesky\FeedGenerator\FeedGenerator;

FeedGenerator::validateAuthUsing(function (?string $jwt, Request $request): ?string {
     [, $payload] = JsonWebToken::explode($jwt);
     return data_get($payload, 'iss');
});
```

## Note
The display of the feed is affected by the "language settings of your account". If the feed is not displayed on Bluesky even though the FeedGenerator is retrieving posts, please check the settings.

## Advanced

Use artisan commands and task schedules to save posts to a database. With algo, you can just retrieve them from the DB. It's up to you how you use it.

## More advanced

We also provide the [WebSocketServeCommand](../src/Console/WebSocketServeCommand.php), which uses WebSocket, but it is intended for users who can use it on their own.
