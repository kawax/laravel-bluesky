Testing
====

Most features are available via `Bluesky` Facade, so you can use standard Laravel mocks when testing.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

Bluesky::shouldReceive('login->getProfile->json')->once()->andReturn([]);
Bluesky::shouldReceive('login->getProfile')->once()->andReturn(new Response(Http::response([])->wait()));
```

This package uses the Laravel Http client. If you write `Http::preventStrayRequests()` in the test `setUp()`, you can immediately detect external requests. If there are any unexpected external requests, you can prevent them by mocking them.

```php
use Illuminate\Support\Facades\Http;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }
```

## Some features that cannot be mocked

### FeedGenerator

There is no mock for FeedGenerator because it is called from the Bluesky server. However, the algorithm part of FeedGenerator can be mocked. Therefore, the following test is possible.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\FeedGenerator\FeedGenerator;

    public function test_feed_generator(): void
    {
        FeedGenerator::register(name: 'test', algo: function (?int $limit, ?string $cursor) {
            $posts = Bluesky::searchPosts(q: '#bluesky')->collect('posts');
            $feed = $posts->map(function (array $post) {
                return ['post' => data_get($post, 'uri')];
            })->toArray();
            return ['feed' => $feed];
        });

        Bluesky::shouldReceive('searchPosts->collect')->once()->andReturn(collect([['uri' => 'at://']]));

        $response = $this->get(route('bluesky.feed.skeleton', ['feed' => 'at://did:/app.bsky.feed.generator/test']));

        $response->assertSuccessful();
        $response->assertJson(['feed' => [['post' => 'at://']]]);
    }
```
