<?php

namespace Tests\Feature\Record;

use Revolution\Bluesky\Record\Follow;
use Revolution\Bluesky\Record\Post;
use Tests\TestCase;

class RecordTest extends TestCase
{
    public function test_follow()
    {
        $follow = Follow::create(did: 'did');

        $this->assertIsArray($follow->toRecord());
        $this->assertArrayHasKey('subject', $follow->toRecord());
        $this->assertArrayHasKey('createdAt', $follow->toRecord());
        $this->assertSame('did', $follow->toRecord()['subject']);
        $this->assertSame($follow::NSID, $follow->toRecord()['$type']);
    }

    public function test_post()
    {
        $post = Post::create(text: 'test');
        $post->createdAt('2024');

        $this->assertIsArray($post->toRecord());
        $this->assertArrayHasKey('text', $post->toRecord());
        $this->assertArrayHasKey('createdAt', $post->toRecord());
        $this->assertSame('2024', $post->toRecord()['createdAt']);
        $this->assertSame($post::NSID, $post->toRecord()['$type']);
    }

    public function test_validator()
    {
        $post = Post::create(text: 'test');

        $this->assertTrue($post->validator()->passes());
    }
}
