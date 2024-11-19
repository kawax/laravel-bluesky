<?php

namespace Tests\Feature\RichText;

use Illuminate\Support\Facades\Http;
use Revolution\Bluesky\RichText\TextBuilder;
use Tests\TestCase;

class TextBuilderTest extends TestCase
{
    public function test_detect_facets_mention()
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

    public function test_detect_facets_link()
    {
        $builder = TextBuilder::make('https://localhost https://localhost? test test (https://localhost) example.com https://')->detectFacets();

        $this->assertIsArray($builder->facets);
        $this->assertSame(0, data_get($builder->facets, '0.index.byteStart'));
        $this->assertSame(17, data_get($builder->facets, '0.index.byteEnd'));
        $this->assertSame(48, data_get($builder->facets, '2.index.byteStart'));
        $this->assertSame(65, data_get($builder->facets, '2.index.byteEnd'));

        $this->assertSame(['https://localhost', 'https://localhost', 'https://localhost'], collect($builder->facets)->pluck('features.0.uri')->toArray());
    }

    public function test_detect_facets_tag()
    {
        $builder = TextBuilder::make('#test #a_ #ゑ #ん #ü #_ #')->detectFacets();

        $this->assertIsArray($builder->facets);
        $this->assertSame(0, data_get($builder->facets, '0.index.byteStart'));
        $this->assertSame(5, data_get($builder->facets, '0.index.byteEnd'));
        $this->assertSame(10, data_get($builder->facets, '2.index.byteStart'));
        $this->assertSame(14, data_get($builder->facets, '2.index.byteEnd'));

        $this->assertSame(['test', 'a', 'ゑ', 'ん', 'ü'], collect($builder->facets)->pluck('features.0.tag')->toArray());
    }

    public function test_detect_facets_all()
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
}
