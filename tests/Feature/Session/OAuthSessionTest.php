<?php

namespace Tests\Feature\Session;

use Revolution\Bluesky\Session\OAuthSession;
use Tests\TestCase;

class OAuthSessionTest extends TestCase
{
    public function test_oauth_did()
    {
        $session = OAuthSession::create([
            'did' => 'test',
        ]);

        $this->assertSame('test', $session->did());
    }
}
