<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $product = Product::factory()->create();

        $response = $this->patchJson(route('v1.products.update', $product), [
            'nome' => 'Nome novo',
        ]);

        $response->assertUnauthorized();
        $response->assertExactJson([
            'message' => 'Não autenticado.',
        ]);
        $this->assertSame($product->nome, $product->fresh()->nome);
    }

    public function test_patch_updates_only_the_sent_fields(): void
    {
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

        $response = $this->withToken($this->token())->patchJson(route('v1.products.update', $product), [
            'estoque' => 5,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.nome', 'Camiseta Básica');
        $response->assertJsonPath('data.sku', 'CAM-001');
        $response->assertJsonPath('data.descricao', 'Camiseta de algodão.');
        $response->assertJsonPath('data.preco', '49.90');
        $response->assertJsonPath('data.categoria.nome', 'Camisetas');
        $response->assertJsonPath('data.status', 'ativo');
        $response->assertJsonPath('data.estoque', 5);
        $this->assertSame(5, $product->fresh()->estoque);
    }

    public function test_put_replaces_the_product_fields(): void
    {
        $camisetas = Category::factory()->create([
            'nome' => 'Camisetas',
        ]);
        $calcados = Category::factory()->create([
            'nome' => 'Calçados',
        ]);
        $product = Product::factory()->create([
            'sku' => 'CAM-001',
            'nome' => 'Camiseta Básica',
            'descricao' => 'Camiseta de algodão.',
            'preco' => '49.90',
            'category_id' => $camisetas->id,
            'estoque' => 20,
        ]);

        $response = $this->withToken($this->token())->putJson(route('v1.products.update', $product), [
            'nome' => 'Tênis Corrida',
            'descricao' => null,
            'preco' => '199.00',
            'categoria_id' => $calcados->id,
            'sku' => 'ten-010',
            'estoque' => 8,
            'status' => 'inativo',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.nome', 'Tênis Corrida');
        $response->assertJsonPath('data.descricao', null);
        $response->assertJsonPath('data.preco', '199.00');
        $response->assertJsonPath('data.sku', 'TEN-010');
        $response->assertJsonPath('data.categoria.nome', 'Calçados');
        $response->assertJsonPath('data.status', 'inativo');
        $response->assertJsonPath('data.estoque', 8);
    }

    public function test_returns_422_when_price_is_negative(): void
    {
        $product = Product::factory()->create([
            'preco' => '49.90',
        ]);

        $response = $this->withToken($this->token())->patchJson(route('v1.products.update', $product), [
            'preco' => -1,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.preco.0', 'O preço não pode ser negativo.');
        $this->assertSame('49.90', $product->fresh()->preco);
    }

    public function test_returns_404_when_product_does_not_exist(): void
    {
        $response = $this->withToken($this->token())->patchJson(route('v1.products.update', ['product' => 999999]), [
            'nome' => 'Nome novo',
        ]);

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
