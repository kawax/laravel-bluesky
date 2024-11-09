<?php

namespace Tests\Feature\Record;

use Revolution\Bluesky\Record\Follow;
use Tests\TestCase;

class RecordTest extends TestCase
{
    public function test_follow()
    {
        $follow = Follow::create(did: 'did');

        $this->assertIsArray($follow->toRecord());
        $this->assertArrayHasKey('subject', $follow->toRecord());
        $this->assertSame('did', $follow->toRecord()['subject']);
    }
}
