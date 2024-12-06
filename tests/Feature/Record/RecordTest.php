<?php

declare(strict_types=1);

namespace Tests\Feature\Record;

use Illuminate\Support\Carbon;
use Revolution\Bluesky\Record\Follow;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\Record\ThreadGate;
use Revolution\Bluesky\RichText\TextBuilder;
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
        $this->travelTo(Carbon::parse('2024-12-31'), function () {
            $post = Post::create(text: 'test');

            $this->assertArrayNotHasKey('createdAt', $post->toArray());
            $this->assertIsArray($post->toRecord());
            $this->assertArrayHasKey('text', $post->toRecord());
            $this->assertArrayHasKey('createdAt', $post->toRecord());
            $this->assertStringStartsWith('2024-12-31', $post->toRecord()['createdAt']);
            $this->assertSame($post::NSID, $post->toRecord()['$type']);
        });
    }

    public function test_post_created()
    {
        $post = Post::create(text: 'test')
            ->createdAt('2024');

        $this->assertIsArray($post->toRecord());
        $this->assertArrayHasKey('createdAt', $post->toRecord());
        $this->assertStringStartsWith('2024', $post->toRecord()['createdAt']);
    }

    public function test_post_build()
    {
        $post = Post::build(fn (TextBuilder $builder): TextBuilder => $builder->text('test ')->tag('#tag', 'tag'));

        $this->assertIsArray($post->toRecord());
        $this->assertSame('test #tag', $post->toRecord()['text']);
    }

    public function test_validator()
    {
        $post = Post::create(text: 'test');

        $this->assertTrue($post->validator()->passes());
    }

    public function test_thread_gate()
    {
        $gate = ThreadGate::create(post: 'at://', allow: [ThreadGate::mention(), ThreadGate::following(), ThreadGate::list('at://')]);

        $this->assertIsArray($gate->toRecord());
        $this->assertArrayHasKey('post', $gate->toRecord());
        $this->assertArrayHasKey('allow', $gate->toRecord());
        $this->assertSame($gate::NSID, $gate->toRecord()['$type']);
    }
}
