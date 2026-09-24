<?php

namespace Tests\Feature\Domain\Catalog\Jobs;

use App\Jobs\SyncProductSearch;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SyncProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_a_product_queues_the_search_sync_when_elasticsearch_is_enabled(): void
    {
        config(['elasticsearch.enabled' => true]);
        Queue::fake();

        $product = Product::factory()->create([
            'nome' => 'Camiseta',
        ]);

        Queue::assertPushed(SyncProductSearch::class, function (SyncProductSearch $job) use ($product): bool {
            return $job->productId === $product->id;
        });
    }

    public function test_deleting_a_product_queues_the_search_sync(): void
    {
        config(['elasticsearch.enabled' => false]);
        $product = Product::factory()->create();

        config(['elasticsearch.enabled' => true]);
        Queue::fake();

        $product->delete();

        Queue::assertPushed(SyncProductSearch::class, function (SyncProductSearch $job) use ($product): bool {
            return $job->productId === $product->id;
        });
    }

    public function test_does_not_queue_the_search_sync_when_elasticsearch_is_disabled(): void
    {
        config(['elasticsearch.enabled' => false]);
        Queue::fake();

        Product::factory()->create();

        Queue::assertNothingPushed();
    }

    public function test_indexes_the_current_product(): void
    {
        config([
            'elasticsearch.enabled' => true,
            'elasticsearch.host' => 'http://elasticsearch.test',
            'elasticsearch.index' => 'products',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'elasticsearch.test/*' => Http::response(['result' => 'created']),
        ]);

        $product = Product::factory()->create([
            'nome' => 'Camiseta Básica',
            'sku' => 'CAM-001',
            'preco' => '49.90',
            'estoque' => 4,
            'status' => 'ativo',
        ]);

        Http::assertSent(function ($request) use ($product): bool {
            return $request->method() === 'PUT'
                && str_ends_with($request->url(), '/_doc/'.$product->id)
                && $request->data()['nome'] === 'Camiseta Básica'
                && $request->data()['sku'] === 'CAM-001'
                && $request->data()['descricao'] === $product->descricao;
        });
    }

    public function test_removes_the_document_when_the_product_no_longer_exists(): void
    {
        config([
            'elasticsearch.enabled' => true,
            'elasticsearch.host' => 'http://elasticsearch.test',
            'elasticsearch.index' => 'products',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'elasticsearch.test/*' => function ($request) {
                if ($request->method() === 'DELETE') {
                    return Http::response([], 404);
                }

                return Http::response(['result' => 'created']);
            },
        ]);

        $product = Product::factory()->create();
        $productId = $product->id;
        $product->delete();

        Http::assertSent(function ($request) use ($productId): bool {
            return $request->method() === 'DELETE'
                && str_ends_with($request->url(), '/_doc/'.$productId);
        });
    }
}
