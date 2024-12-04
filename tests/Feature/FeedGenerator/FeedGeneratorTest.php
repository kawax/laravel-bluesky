<?php

namespace Tests\Feature\FeedGenerator;

use Illuminate\Http\Request;
use InvalidArgumentException;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Serializer\PublicKey\DerPublicKeySerializer;
use Mdanter\Ecc\Serializer\PublicKey\PemPublicKeySerializer;
use PHPUnit\Framework\Attributes\RequiresMethod;
use Revolution\Bluesky\Crypto\DidKey;
use Revolution\Bluesky\Crypto\K256;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\FeedGenerator\FeedGenerator;
use Revolution\Bluesky\FeedGenerator\ValidateAuth;
use Revolution\Bluesky\Socialite\Key\JsonWebToken;
use Tests\TestCase;

class FeedGeneratorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        FeedGenerator::flush();
        FeedGenerator::validateAuthUsing(null);
    }

    public function test_feed_register(): void
    {
        FeedGenerator::register('test', function (?int $limit, ?string $cursor): array {
            return [];
        });

        $this->assertTrue(FeedGenerator::has('test'));
    }

    public function test_feed_register_callable_class(): void
    {
        FeedGenerator::register('test', new TestFeed);

        $this->assertTrue(FeedGenerator::has('test'));
    }

    public function test_feed_register_class_string(): void
    {
        FeedGenerator::register('test', TestFeed::class);

        $this->assertTrue(FeedGenerator::has('test'));
    }

    public function test_feed_register_class_not_callable(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FeedGenerator::register('test', TestFeed2::class);

        $this->assertTrue(FeedGenerator::has('test'));
    }

    public function test_feed_register_class_callable_array(): void
    {
        FeedGenerator::register('test', [new TestFeed2(), 'feed']);

        $this->assertTrue(FeedGenerator::has('test'));
    }

    public function test_feed_register_first_class_callable_syntax(): void
    {
        FeedGenerator::register('test', (new TestFeed2)->feed(...));

        $this->assertTrue(FeedGenerator::has('test'));
    }

    public function test_feed_http(): void
    {
        FeedGenerator::register('test', function (?int $limit, ?string $cursor): array {
            return ['feed' => [['post' => 'at://']]];
        });

        $this->mock(ValidateAuth::class, function ($mock) {
            $mock->shouldReceive('__invoke')->once()->andReturn('did');
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

    public function test_validate_auth_using()
    {
        FeedGenerator::validateAuthUsing(function (?string $jwt, Request $request) {
            return 'did';
        });

        FeedGenerator::register('test', function (?int $limit, ?string $cursor): array {
            return ['feed' => [['post' => 'at://']]];
        });

        $response = $this->get(route('bluesky.feed.skeleton', ['feed' => 'at://did:/app.bsky.feed.generator/test']));

        $response->assertSuccessful();
        $response->assertJson(['feed' => [['post' => 'at://']]]);
    }

    #[RequiresMethod(PemPublicKeySerializer::class, 'parse')]
    public function test_feed_validate_auth(): void
    {
        FeedGenerator::register('test', function (?int $limit, ?string $cursor, ?string $user): array {
            return ['user' => $user];
        });

        $key = K256::create();

        $jwt = JsonWebToken::encode(
            head: ['typ' => 'JWT', 'alg' => K256::ALG],
            payload: [
                'iss' => 'did:plc:alice',
                'exp' => now()->addDay()->timestamp,
            ],
            key: $key->privatePEM(),
            alg: K256::ALG,
        );

        $pubkey = $key->publicPEM();
        $derSerializer = new DerPublicKeySerializer(EccFactory::getAdapter());
        $pemSerializer = new PemPublicKeySerializer($derSerializer);
        $key = $pemSerializer->parse($pubkey);
        $didkey = DidKey::encode($key);

        Bluesky::shouldReceive('identity->resolveDID->json')->once()->andReturn([
            'verificationMethod' => [
                [
                    'type' => 'Multikey',
                    'publicKeyMultibase' => $didkey,
                ],
            ],
        ]);

        $response = $this->withToken($jwt)->get(route('bluesky.feed.skeleton', ['feed' => 'at://did:/app.bsky.feed.generator/test']));

        $response->assertSuccessful();
        $response->assertJson(['user' => 'did:plc:alice']);
    }
}

class TestFeed
{
    public function __invoke(?int $limit, ?string $cursor): array
    {
        return [];
    }
}

class TestFeed2
{
    public function feed(?int $limit, ?string $cursor): array
    {
        return [];
    }
}
