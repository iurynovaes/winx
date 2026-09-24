<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Catalog;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $response = $this->getJson(route('v1.products.search', ['q' => 'camisa']));

        $response->assertUnauthorized();
        $response->assertExactJson([
            'message' => 'Não autenticado.',
        ]);
    }

    public function test_returns_422_when_the_term_is_missing(): void
    {
        config(['elasticsearch.enabled' => true]);

        $response = $this->withToken($this->token())->getJson(route('v1.products.search'));

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.q.0', 'Informe o termo.');
    }

    public function test_returns_503_when_elasticsearch_is_disabled(): void
    {
        config(['elasticsearch.enabled' => false]);

        $response = $this->withToken($this->token())->getJson(route('v1.products.search', [
            'q' => 'camisa',
        ]));

        $response->assertServiceUnavailable();
        $response->assertExactJson([
            'message' => 'A busca inteligente está desligada.',
        ]);
    }

    public function test_returns_products_in_relevance_order(): void
    {
        config([
            'elasticsearch.enabled' => true,
            'elasticsearch.host' => 'http://elasticsearch.test',
            'elasticsearch.index' => 'products',
        ]);

        $ids = ['anel' => 0, 'bone' => 0];

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$ids) {
            if (str_contains($request->url(), '/_search')) {
                return Http::response([
                    'hits' => [
                        'total' => ['value' => 2],
                        'hits' => [
                            ['_id' => (string) $ids['bone']],
                            ['_id' => (string) $ids['anel']],
                        ],
                    ],
                ]);
            }

            return Http::response(['result' => 'created']);
        });

        $anel = Product::factory()->create(['nome' => 'Anel']);
        $bone = Product::factory()->create(['nome' => 'Boné']);
        $ids = [
            'anel' => $anel->id,
            'bone' => $bone->id,
        ];

        $response = $this->withToken($this->token())->getJson(route('v1.products.search', [
            'q' => 'acessorio',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $bone->id);
        $response->assertJsonPath('data.1.id', $anel->id);
        $response->assertJsonPath('meta.total', 2);

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), '/_search')
                && $request->data()['query']['multi_match']['query'] === 'acessorio'
                && $request->data()['query']['multi_match']['fields'] === ['nome^3', 'descricao', 'sku'];
        });
    }

    private function token(): string
    {
        return User::factory()->create()->createToken('api')->plainTextToken;
    }
}
