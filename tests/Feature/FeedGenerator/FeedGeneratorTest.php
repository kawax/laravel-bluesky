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

        $response = $this->get(route('bluesky.feed.skeleton', ['feed' => 'at://did:/app.bsky.feed.generator/test']));

        $response->assertSuccessful();
        $response->assertJson(['feed' => [['post' => 'at://']]]);
    }

    public function test_feed_http_missing(): void
    {
        FeedGenerator::register('test', function (?int $limit, ?string $cursor): array {
            return ['feed' => [['post' => 'at://']]];
        });

        $response = $this->get(route('bluesky.feed.skeleton', ['feed' => 'at://did:/app.bsky.feed.generator/miss']));

        $response->assertNotFound();
    }

    public function test_feed_describe(): void
    {
        FeedGenerator::register('test', function (?int $limit, ?string $cursor): array {
            return ['feed' => [['post' => 'at://']]];
        });

        $response = $this->get(route('bluesky.feed.describe'));

        $response->assertSuccessful();
        $response->assertJson(['did' => 'did:web:localhost', 'feeds' => ['at://did:web:localhost/app.bsky.feed.generator/test']]);
    }

    public function test_feed_did(): void
    {
        $response = $this->get(route('bluesky.well-known.did'));

        $response->assertSuccessful();
    }

    public function test_feed_atproto_did(): void
    {
        $response = $this->get(route('bluesky.well-known.atproto'));

        $response->assertSuccessful();
    }
}
