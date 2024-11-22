<?php

namespace Tests\Feature\FeedGenerator;

use Revolution\Bluesky\FeedGenerator\FeedGenerator;
use Tests\TestCase;

class FeedGeneratorTest extends TestCase
{
    public function test_feed_register(): void
    {
        FeedGenerator::register('test', function (?int $limit, ?string $cursor): array {
            return [];
        });

        $this->assertTrue(FeedGenerator::has('test'));
    }

    public function test_feed_http(): void
    {
        FeedGenerator::register('test', function (?int $limit, ?string $cursor): array {
            return ['feed' => [['post' => 'at://']]];
        });

        $response = $this->get(route('bluesky.feed.generator', ['feed' => 'at://did:/app.bsky.feed.generator/test']));

        $response->assertSuccessful();
        $response->assertJson(['feed' => [['post' => 'at://']]]);
    }

    public function test_feed_http_missing(): void
    {
        FeedGenerator::register('test', function (?int $limit, ?string $cursor): array {
            return ['feed' => [['post' => 'at://']]];
        });

        $response = $this->get(route('bluesky.feed.generator', ['feed' => 'at://did:/app.bsky.feed.generator/miss']));

        $response->assertNotFound();
    }
}
