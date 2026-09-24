<?php

namespace Tests\Feature\Console;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IndexProductsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_fails_when_elasticsearch_is_disabled(): void
    {
        config(['elasticsearch.enabled' => false]);

        $this->artisan('catalog:index-products')
            ->expectsOutputToContain('O Elasticsearch está desligado.')
            ->assertFailed();
    }

    public function test_indexes_every_product(): void
    {
        Product::factory()->count(2)->create();

        config([
            'elasticsearch.enabled' => true,
            'elasticsearch.host' => 'http://elasticsearch.test',
            'elasticsearch.index' => 'products',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'elasticsearch.test/*' => Http::response(['errors' => false]),
        ]);

        $this->artisan('catalog:index-products')
            ->expectsOutputToContain('2 produtos indexados.')
            ->assertSuccessful();

        Http::assertSent(function ($request): bool {
            return $request->method() === 'POST'
                && str_ends_with($request->url(), '/_bulk');
        });
    }
}
