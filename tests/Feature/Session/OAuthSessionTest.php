<?php

namespace Tests\Feature\Session;

use Illuminate\Support\Collection;
use Revolution\Bluesky\Session\OAuthSession;
use Tests\TestCase;

class OAuthSessionTest extends TestCase
{
    public function test_oauth_session()
    {
        $session = OAuthSession::create([
            'did' => 'test',
            'displayName' => 'name',
            'avatar' => 'https://',

            'test' => '',
        ])->except('test');

        $this->assertSame('test', $session->did());
        $this->assertSame('name', $session->displayName());
        $this->assertSame('https://', $session->avatar());
        $this->assertFalse($session->has('test'));
        $this->assertInstanceOf(Collection::class, $session->collect());
    }
}
