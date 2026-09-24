<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $response = $this->postJson(route('v1.products.store'), $this->payload());

        $response->assertUnauthorized();
        $response->assertExactJson([
            'message' => 'Não autenticado.',
        ]);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_valid_payload_creates_product_and_returns_201(): void
    {
        $this->travelTo('2026-09-24 12:00:00');

        $category = Category::factory()->create([
            'nome' => 'Camisetas',
        ]);

        $response = $this->withToken($this->token())->postJson(route('v1.products.store'), [
            'nome' => 'Camiseta Básica',
            'preco' => 49.9,
            'categoria_id' => $category->id,
            'sku' => 'cam-001',
            'estoque' => 20,
            'created_at' => '2000-01-01T00:00:00.000000Z',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.nome', 'Camiseta Básica');
        $response->assertJsonPath('data.descricao', null);
        $response->assertJsonPath('data.preco', '49.90');
        $response->assertJsonPath('data.sku', 'CAM-001');
        $response->assertJsonPath('data.status', 'ativo');
        $response->assertJsonPath('data.categoria.id', $category->id);
        $response->assertJsonPath('data.categoria.nome', 'Camisetas');
        $response->assertJsonPath('data.estoque', 20);
        $response->assertJsonPath('data.created_at', '2026-09-24T12:00:00.000000Z');
        $response->assertJsonPath('data.updated_at', '2026-09-24T12:00:00.000000Z');

        $product = Product::query()->where('nome', 'Camiseta Básica')->first();

        $this->assertNotNull($product);
        $this->assertSame('49.90', $product->preco);
        $this->assertNull($product->descricao);
        $this->assertSame(20, $product->estoque);
    }

    public function test_returns_422_when_payload_is_empty(): void
    {
        $response = $this->withToken($this->token())->postJson(route('v1.products.store'), []);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'Os dados enviados são inválidos.');
        $response->assertJsonPath('errors.nome.0', 'Informe o nome.');
        $response->assertJsonPath('errors.preco.0', 'Informe o preço.');
        $response->assertJsonPath('errors.categoria_id.0', 'Informe a categoria.');
        $response->assertJsonPath('errors.sku.0', 'Informe o SKU.');
        $response->assertJsonPath('errors.estoque.0', 'Informe o estoque.');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_returns_422_when_price_is_negative(): void
    {
        $response = $this->withToken($this->token())->postJson(route('v1.products.store'), $this->payload([
            'preco' => -1,
        ]));

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.preco.0', 'O preço não pode ser negativo.');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_returns_422_when_price_has_too_many_decimals(): void
    {
        $response = $this->withToken($this->token())->postJson(route('v1.products.store'), $this->payload([
            'preco' => 10.999,
        ]));

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.preco.0', 'O preço deve ter no máximo 2 casas decimais.');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_returns_422_when_stock_is_negative(): void
    {
        $response = $this->withToken($this->token())->postJson(route('v1.products.store'), $this->payload([
            'estoque' => -1,
        ]));

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.estoque.0', 'O estoque não pode ser negativo.');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_returns_422_when_stock_is_not_an_integer(): void
    {
        $response = $this->withToken($this->token())->postJson(route('v1.products.store'), $this->payload([
            'estoque' => 1.5,
        ]));

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.estoque.0', 'O estoque deve ser um número inteiro.');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_returns_422_when_name_is_too_long(): void
    {
        $response = $this->withToken($this->token())->postJson(route('v1.products.store'), $this->payload([
            'nome' => str_repeat('a', 256),
        ]));

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.nome.0', 'O nome deve ter no máximo 255 caracteres.');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_returns_422_when_sku_is_already_used(): void
    {
        Product::factory()->create([
            'sku' => 'CAM-001',
        ]);

        $response = $this->withToken($this->token())->postJson(route('v1.products.store'), $this->payload([
            'sku' => 'cam-001',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.sku.0', 'Este SKU já está em uso.');
        $this->assertDatabaseCount('products', 1);
    }

    public function test_returns_422_when_category_does_not_exist(): void
    {
        $response = $this->withToken($this->token())->postJson(route('v1.products.store'), $this->payload([
            'categoria_id' => 999999,
        ]));

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.categoria_id.0', 'A categoria informada não existe.');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_returns_422_when_status_is_invalid(): void
    {
        $response = $this->withToken($this->token())->postJson(route('v1.products.store'), $this->payload([
            'status' => 'arquivado',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.status.0', 'O status deve ser ativo ou inativo.');
        $this->assertDatabaseCount('products', 0);
    }

    private function token(): string
    {
        return User::factory()->create()->createToken('api')->plainTextToken;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nome' => 'Camiseta Básica',
            'descricao' => 'Camiseta de algodão.',
            'preco' => '49.90',
            'categoria_id' => Category::factory()->create()->id,
            'sku' => 'CAM-001',
            'estoque' => 20,
        ], $overrides);
    }
}
