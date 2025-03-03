<?php

declare(strict_types=1);

namespace Tests\Feature\RichText;

use Illuminate\Support\Facades\Http;
use Revolution\Bluesky\Record\Post;
use Revolution\Bluesky\RichText\TextBuilder;
use Tests\TestCase;

class TextBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_detect_facets_mention(): void
    {
        Http::fakeSequence()
            ->push(['did' => 'did:plc:alice'])
            ->push(['did' => 'did:plc:alice'])
            ->push(['did' => 'did:plc:bob'])
            ->whenEmpty(Http::response(['did' => 'did']));

        $builder = TextBuilder::make('@alice.test @alice.test @bob.test test @')->detectFacets();

        $this->assertIsArray($builder->facets);
        $this->assertSame(0, data_get($builder->facets, '0.index.byteStart'));
        $this->assertSame(11, data_get($builder->facets, '0.index.byteEnd'));
        $this->assertSame(24, data_get($builder->facets, '2.index.byteStart'));
        $this->assertSame(33, data_get($builder->facets, '2.index.byteEnd'));

        $this->assertSame(['did:plc:alice', 'did:plc:alice', 'did:plc:bob'], collect($builder->facets)->pluck('features.0.did')->toArray());
    }

    public function test_detect_facets_link(): void
    {
        $builder = TextBuilder::make('https://localhost https://localhost/?test=a test test (https://localhost) example.com https://localhost/.,;:!? https://localhost/#hash https://')->detectFacets();

        $this->assertIsArray($builder->facets);
        $this->assertSame(0, data_get($builder->facets, '0.index.byteStart'));
        $this->assertSame(17, data_get($builder->facets, '0.index.byteEnd'));
        $this->assertSame(55, data_get($builder->facets, '2.index.byteStart'));
        $this->assertSame(72, data_get($builder->facets, '2.index.byteEnd'));

        $this->assertSame(['https://localhost', 'https://localhost/?test=a', 'https://localhost', 'https://example.com', 'https://localhost/', 'https://localhost/#hash'], collect($builder->facets)->pluck('features.0.uri')->toArray());
    }

    public function test_detect_facets_tag(): void
    {
        $builder = TextBuilder::make('#test #a_ #ゑ ＃ん #ü #_ #😇 #')->detectFacets();

        $this->assertIsArray($builder->facets);
        $this->assertSame(0, data_get($builder->facets, '0.index.byteStart'));
        $this->assertSame(5, data_get($builder->facets, '0.index.byteEnd'));
        $this->assertSame(10, data_get($builder->facets, '2.index.byteStart'));
        $this->assertSame(14, data_get($builder->facets, '2.index.byteEnd'));
        $this->assertSame(15, data_get($builder->facets, '3.index.byteStart'));
        $this->assertSame(21, data_get($builder->facets, '3.index.byteEnd'));

        $this->assertSame(['test', 'a', 'ゑ', 'ん', 'ü', '😇'], collect($builder->facets)->pluck('features.0.tag')->toArray());
    }

    public function test_detect_facets_all(): void
    {
        Http::fakeSequence()
            ->push(['did' => 'did:plc:alice']);

        $builder = TextBuilder::make('@alice.test https://localhost #alice')->detectFacets();

        $this->assertIsArray($builder->facets);
        $this->assertSame(0, data_get($builder->facets, '0.index.byteStart'));
        $this->assertSame(11, data_get($builder->facets, '0.index.byteEnd'));
        $this->assertSame(12, data_get($builder->facets, '1.index.byteStart'));
        $this->assertSame(29, data_get($builder->facets, '1.index.byteEnd'));
        $this->assertSame(30, data_get($builder->facets, '2.index.byteStart'));
        $this->assertSame(36, data_get($builder->facets, '2.index.byteEnd'));

        $this->assertSame('did:plc:alice', collect($builder->facets)->dot()->get('0.features.0.did'));
        $this->assertSame('https://localhost', collect($builder->facets)->dot()->get('1.features.0.uri'));
        $this->assertSame('alice', collect($builder->facets)->dot()->get('2.features.0.tag'));
    }

    public function test_detect_facets_post_build(): void
    {
        Http::fakeSequence()
            ->push(['did' => 'did:plc:alice']);

        $post = Post::build(function (TextBuilder $builder) {
            $builder->text('@alice.test https://localhost #alice')
                ->detectFacets()
                ->newLine()
                ->tag('#Additional_tag', 'Additional_tag');
        });

        $facets = $post->toArray()['facets'];

        $this->assertIsArray($facets);
        $this->assertSame(0, data_get($facets, '0.index.byteStart'));
        $this->assertSame(11, data_get($facets, '0.index.byteEnd'));
        $this->assertSame(12, data_get($facets, '1.index.byteStart'));
        $this->assertSame(29, data_get($facets, '1.index.byteEnd'));
        $this->assertSame(30, data_get($facets, '2.index.byteStart'));
        $this->assertSame(36, data_get($facets, '2.index.byteEnd'));
        $this->assertSame(37, data_get($facets, '3.index.byteStart'));
        $this->assertSame(52, data_get($facets, '3.index.byteEnd'));

        $this->assertSame('did:plc:alice', collect($facets)->dot()->get('0.features.0.did'));
        $this->assertSame('https://localhost', collect($facets)->dot()->get('1.features.0.uri'));
        $this->assertSame('alice', collect($facets)->dot()->get('2.features.0.tag'));
    }

    public function test_resolve_mention(): void
    {
        Http::fakeSequence()
            ->push(['did' => 'did:plc:alice']);

        $builder = TextBuilder::make('test')
            ->mention('@alice.test ');

        $this->assertSame(4, data_get($builder->facets, '0.index.byteStart'));
        $this->assertSame(15, data_get($builder->facets, '0.index.byteEnd'));
        $this->assertSame('did:plc:alice', collect($builder->facets)->dot()->get('0.features.0.did'));
    }

    public function test_resolve_link(): void
    {
        $builder = TextBuilder::make('test')
            ->link('https://localhost ');

        $this->assertSame(4, data_get($builder->facets, '0.index.byteStart'));
        $this->assertSame(21, data_get($builder->facets, '0.index.byteEnd'));
        $this->assertSame('https://localhost', collect($builder->facets)->dot()->get('0.features.0.uri'));
    }

    public function test_resolve_tag(): void
    {
        $builder = TextBuilder::make('test')
            ->tag('#alice ');

        $this->assertSame(4, data_get($builder->facets, '0.index.byteStart'));
        $this->assertSame(10, data_get($builder->facets, '0.index.byteEnd'));
        $this->assertSame('alice', collect($builder->facets)->dot()->get('0.features.0.tag'));
    }
}
