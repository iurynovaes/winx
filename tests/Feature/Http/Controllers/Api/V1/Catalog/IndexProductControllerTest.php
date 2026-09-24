<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IndexProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $response = $this->getJson(route('v1.products.index'));

        $response->assertUnauthorized();
        $response->assertExactJson([
            'message' => 'Não autenticado.',
        ]);
    }

    public function test_returns_products_ordered_by_name_then_id(): void
    {
        $laterMeia = Product::factory()->create([
            'nome' => 'Meia',
            'status' => 'inativo',
        ]);
        $anel = Product::factory()->create([
            'nome' => 'Anel',
        ]);
        $earlierMeia = Product::factory()->create([
            'nome' => 'Meia',
        ]);

        $response = $this->withToken($this->token())->getJson(route('v1.products.index'));

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $anel->id);
        $response->assertJsonPath('data.1.id', $laterMeia->id);
        $response->assertJsonPath('data.2.id', $earlierMeia->id);
        $response->assertJsonPath('meta.total', 3);
        $response->assertJsonPath('meta.per_page', 15);
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.last_page', 1);
        $response->assertJsonPath('data.0.nome', 'Anel');
        $response->assertJsonPath('data.0.categoria.id', $anel->category_id);
    }

    public function test_returns_an_empty_page_when_no_product_exists(): void
    {
        $response = $this->withToken($this->token())->getJson(route('v1.products.index'));

        $response->assertOk();
        $response->assertJsonPath('data', []);
        $response->assertJsonPath('meta.total', 0);
    }

    public function test_returns_the_requested_page_size(): void
    {
        Product::factory()->create(['nome' => 'Anel']);
        Product::factory()->create(['nome' => 'Boné']);
        Product::factory()->create(['nome' => 'Camisa']);

        $response = $this->withToken($this->token())->getJson(route('v1.products.index', [
            'per_page' => 2,
            'page' => 2,
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.nome', 'Camisa');
        $response->assertJsonPath('meta.total', 3);
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.current_page', 2);
        $response->assertJsonPath('meta.last_page', 2);
        $this->assertStringContainsString('per_page=2', (string) $response->json('links.prev'));
    }

    public function test_filters_by_name_without_case_sensitivity(): void
    {
        Product::factory()->create(['nome' => 'Camiseta Básica']);
        Product::factory()->create(['nome' => 'Calça Jeans']);

        $response = $this->withToken($this->token())->getJson(route('v1.products.index', [
            'nome' => 'CAMISETA',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.nome', 'Camiseta Básica');
        $response->assertJsonPath('meta.total', 1);
    }

    public function test_name_search_treats_wildcards_as_text(): void
    {
        Product::factory()->create(['nome' => '1000 unidades']);
        Product::factory()->create(['nome' => '100% Algodão']);

        $response = $this->withToken($this->token())->getJson(route('v1.products.index', [
            'nome' => '100%',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.nome', '100% Algodão');
    }

    public function test_name_search_does_not_interpolate_the_term_into_sql(): void
    {
        Product::factory()->create(['nome' => 'Camiseta']);
        Product::factory()->create(['nome' => 'Calça']);

        $response = $this->withToken($this->token())->getJson(route('v1.products.index', [
            'nome' => "%' OR 1=1 --",
        ]));

        $response->assertOk();
        $response->assertJsonPath('meta.total', 0);
    }

    public function test_filters_by_category(): void
    {
        $shirts = Category::factory()->create(['nome' => 'Camisetas']);
        $shoes = Category::factory()->create(['nome' => 'Calçados']);
        Product::factory()->create([
            'nome' => 'Camiseta',
            'category_id' => $shirts->id,
        ]);
        Product::factory()->create([
            'nome' => 'Tênis',
            'category_id' => $shoes->id,
        ]);

        $response = $this->withToken($this->token())->getJson(route('v1.products.index', [
            'categoria_id' => $shirts->id,
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.nome', 'Camiseta');
    }

    public function test_filters_by_minimum_price(): void
    {
        Product::factory()->create(['nome' => 'Barato', 'preco' => '10.00']);
        Product::factory()->create(['nome' => 'Limite', 'preco' => '20.00']);
        Product::factory()->create(['nome' => 'Caro', 'preco' => '30.00']);

        $response = $this->withToken($this->token())->getJson(route('v1.products.index', [
            'preco_min' => '20.00',
        ]));

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.nome', 'Caro');
        $response->assertJsonPath('data.1.nome', 'Limite');
    }

    public function test_filters_by_maximum_price(): void
    {
        Product::factory()->create(['nome' => 'Barato', 'preco' => '10.00']);
        Product::factory()->create(['nome' => 'Limite', 'preco' => '20.00']);
        Product::factory()->create(['nome' => 'Caro', 'preco' => '30.00']);

        $response = $this->withToken($this->token())->getJson(route('v1.products.index', [
            'preco_max' => '20.00',
        ]));

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.nome', 'Barato');
        $response->assertJsonPath('data.1.nome', 'Limite');
    }

    public function test_filters_products_with_stock(): void
    {
        Product::factory()->create(['nome' => 'Esgotado', 'estoque' => 0]);
        Product::factory()->create(['nome' => 'Disponível', 'estoque' => 3]);

        $response = $this->withToken($this->token())->getJson(route('v1.products.index', [
            'disponivel' => '1',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.nome', 'Disponível');
    }

    public function test_filters_products_without_stock(): void
    {
        Product::factory()->create(['nome' => 'Esgotado', 'estoque' => 0]);
        Product::factory()->create(['nome' => 'Disponível', 'estoque' => 3]);

        $response = $this->withToken($this->token())->getJson(route('v1.products.index', [
            'disponivel' => '0',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.nome', 'Esgotado');
    }

    public function test_filters_by_status(): void
    {
        Product::factory()->create(['nome' => 'Ativo', 'status' => 'ativo']);
        Product::factory()->create(['nome' => 'Inativo', 'status' => 'inativo']);

        $response = $this->withToken($this->token())->getJson(route('v1.products.index', [
            'status' => 'inativo',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.nome', 'Inativo');
    }

    public function test_combines_filters(): void
    {
        $shirts = Category::factory()->create(['nome' => 'Camisetas']);
        Product::factory()->create([
            'nome' => 'Camiseta Azul',
            'category_id' => $shirts->id,
            'preco' => '40.00',
            'estoque' => 2,
            'status' => 'ativo',
        ]);
        Product::factory()->create([
            'nome' => 'Camiseta Vermelha',
            'category_id' => $shirts->id,
            'preco' => '80.00',
            'estoque' => 2,
            'status' => 'ativo',
        ]);
        Product::factory()->create([
            'nome' => 'Camiseta Sem Estoque',
            'category_id' => $shirts->id,
            'preco' => '40.00',
            'estoque' => 0,
            'status' => 'ativo',
        ]);

        $response = $this->withToken($this->token())->getJson(route('v1.products.index', [
            'nome' => 'camiseta',
            'categoria_id' => $shirts->id,
            'preco_min' => '30',
            'preco_max' => '50',
            'disponivel' => 'true',
            'status' => 'ativo',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.nome', 'Camiseta Azul');
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: string}>
     */
    public static function invalidFilters(): array
    {
        return [
            'unknown category' => [['categoria_id' => 999999], 'categoria_id', 'A categoria informada não existe.'],
            'negative minimum price' => [['preco_min' => -1], 'preco_min', 'O preço mínimo não pode ser negativo.'],
            'maximum below minimum' => [['preco_min' => 20, 'preco_max' => 10], 'preco_max', 'O preço máximo deve ser maior ou igual ao preço mínimo.'],
            'invalid availability' => [['disponivel' => 'talvez'], 'disponivel', 'A disponibilidade deve ser verdadeira ou falsa.'],
            'invalid status' => [['status' => 'arquivado'], 'status', 'O status deve ser ativo ou inativo.'],
            'page size above the limit' => [['per_page' => 51], 'per_page', 'A quantidade por página deve ser no máximo 50.'],
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     */
    #[DataProvider('invalidFilters')]
    public function test_returns_422_when_a_filter_is_invalid(array $query, string $field, string $message): void
    {
        $response = $this->withToken($this->token())->getJson(route('v1.products.index', $query));

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'Os dados enviados são inválidos.');
        $response->assertJsonPath('errors.'.$field.'.0', $message);
    }

    private function token(): string
    {
        return User::factory()->create()->createToken('api')->plainTextToken;
    }
}
