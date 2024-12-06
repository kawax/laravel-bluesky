<?php

declare(strict_types=1);

namespace Tests\Feature\Validation;

use Illuminate\Support\Facades\Validator;
use Revolution\Bluesky\Validation\AtActor;
use Revolution\Bluesky\Validation\AtDID;
use Revolution\Bluesky\Validation\AtHandle;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    public function test_did_passes()
    {
        $validator = Validator::make(
            data: ['did' => 'did:plc:test'],
            rules: ['did' => new AtDID()],
        );

        $this->assertTrue($validator->passes());
    }

    public function test_did_fails()
    {
        $validator = Validator::make(
            data: ['did' => ''],
            rules: ['did' => new AtDID()],
        );

        $this->assertTrue($validator->fails());
    }

    public function test_handle_passes()
    {
        $validator = Validator::make(
            data: ['handle' => 'alice.local'],
            rules: ['handle' => new AtHandle()],
        );

        $this->assertTrue($validator->passes());
    }

    public function test_handle_fails()
    {
        $validator = Validator::make(
            data: ['handle' => ''],
            rules: ['handle' => new AtHandle()],
        );

        $this->assertTrue($validator->fails());
    }

    public function test_actor_passes()
    {
        $validator = Validator::make(
            data: [
                'did' => 'did:plc:test',
                'handle' => 'alice.local',
            ],
            rules: [
                'did' => [new AtActor()],
                'handle' => [new AtActor()],
            ],
        );

        $this->assertTrue($validator->passes());
    }

    public function test_did_or_handle_fails()
    {
        $validator = Validator::make(
            data: [
                'did' => '',
                'handle' => null,
            ],
            rules: [
                'did' => [new AtActor()],
                'handle' => [new AtActor()],
            ],
        );

        $this->assertTrue($validator->fails());
    }
}
