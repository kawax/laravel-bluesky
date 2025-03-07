<?php

declare(strict_types=1);

namespace Tests\Feature\Session;

use Illuminate\Support\Collection;
use Revolution\Bluesky\Session\OAuthSession;
use Tests\TestCase;

class OAuthSessionTest extends TestCase
{
    public function test_oauth_session(): void
    {
        $session = OAuthSession::create([
            'did' => 'did:plc:test',
            'profile' => [
                'did' => 'did:plc:profile',
                'displayName' => 'name',
                'avatar' => 'https://',
            ],

            'test' => '',
        ])->except('test');

        $session = OAuthSession::create($session);

        $this->assertSame('did:plc:test', $session->did());
        $this->assertSame('name', $session->displayName());
        $this->assertSame('https://', $session->avatar());
        $this->assertFalse($session->has('test'));
        $this->assertInstanceOf(Collection::class, $session->collect());
    }

    public function test_merge(): void
    {
        $session = OAuthSession::create([
            'did' => 'test',
        ])->merge(['test' => 'test']);

        $this->assertSame('test', $session->get('test'));
        $this->assertTrue($session->has('test'));
    }

    public function test_forget(): void
    {
        $session = OAuthSession::create([
            'did' => 'test',
            'test' => 'test',
        ])->forget('test');

        $this->assertSame(null, $session->get('test'));
        $this->assertFalse($session->has('test'));
    }

    public function test_put(): void
    {
        $session = OAuthSession::create()->put('test', 'test');

        $this->assertSame('test', $session->get('test'));
        $this->assertTrue($session->has('test'));
    }
}
