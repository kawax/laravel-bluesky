<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Tests\TestCase;

class CommandTest extends TestCase
{
    public function test_new_key(): void
    {
        $this->artisan('bluesky:new-private-key')
            ->assertOk()
            ->expectsOutputToContain('BLUESKY_OAUTH_PRIVATE_KEY');
    }
}
