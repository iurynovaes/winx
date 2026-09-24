<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Catalog;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SuggestProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $response = $this->getJson(route('v1.products.suggestions', ['q' => 'cam']));

        $response->assertUnauthorized();
        $response->assertExactJson([
            'message' => 'Não autenticado.',
        ]);
    }

    public function test_returns_422_when_the_term_is_missing(): void
    {
        $response = $this->withToken($this->token())->getJson(route('v1.products.suggestions'));

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'Os dados enviados são inválidos.');
        $response->assertJsonPath('errors.q.0', 'Informe o termo.');
    }

    public function test_returns_503_when_elasticsearch_is_disabled(): void
    {
        config(['elasticsearch.enabled' => false]);

        $response = $this->withToken($this->token())->getJson(route('v1.products.suggestions', [
            'q' => 'cami',
        ]));

        $response->assertServiceUnavailable();
        $response->assertExactJson([
            'message' => 'A busca inteligente está desligada.',
        ]);
    }

    public function test_returns_elasticsearch_suggestions_when_it_is_enabled(): void
    {
        config([
            'elasticsearch.enabled' => true,
            'elasticsearch.host' => 'http://elasticsearch.test',
            'elasticsearch.index' => 'products',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'elasticsearch.test/*' => Http::response([
                'suggest' => [
                    'products' => [
                        [
                            'options' => [
                                ['text' => 'Camiseta Básica'],
                                ['text' => 'Camisa Polo'],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->withToken($this->token())->getJson(route('v1.products.suggestions', [
            'q' => 'cami',
        ]));

        $response->assertOk();
        $response->assertExactJson([
            'data' => ['Camiseta Básica', 'Camisa Polo'],
        ]);
    }

    private function token(): string
    {
        return User::factory()->create()->createToken('api')->plainTextToken;
    }
}
