<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('v1.products.show', $product));

        $response->assertUnauthorized();
        $response->assertExactJson([
            'message' => 'Não autenticado.',
        ]);
    }

    public function test_returns_the_product(): void
    {
        $this->travelTo('2026-09-24 12:00:00');

        $category = Category::factory()->create([
            'nome' => 'Camisetas',
        ]);
        $product = Product::factory()->create([
            'sku' => 'CAM-001',
            'nome' => 'Camiseta Básica',
            'descricao' => 'Camiseta de algodão.',
            'preco' => '49.90',
            'category_id' => $category->id,
            'estoque' => 20,
            'status' => 'ativo',
        ]);

        $response = $this->withToken($this->token())->getJson(route('v1.products.show', $product));

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => $product->id,
                'sku' => 'CAM-001',
                'nome' => 'Camiseta Básica',
                'descricao' => 'Camiseta de algodão.',
                'preco' => '49.90',
                'categoria' => [
                    'id' => $category->id,
                    'nome' => 'Camisetas',
                ],
                'estoque' => 20,
                'status' => 'ativo',
                'created_at' => '2026-09-24T12:00:00.000000Z',
                'updated_at' => '2026-09-24T12:00:00.000000Z',
            ],
        ]);
    }

    public function test_returns_404_when_product_does_not_exist(): void
    {
        $response = $this->withToken($this->token())->getJson(route('v1.products.show', ['product' => 999999]));

        $response->assertNotFound();
        $response->assertExactJson([
            'message' => 'Recurso não encontrado.',
        ]);
    }

    private function token(): string
    {
        return User::factory()->create()->createToken('api')->plainTextToken;
    }
}
