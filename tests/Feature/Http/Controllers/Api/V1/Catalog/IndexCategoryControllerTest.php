<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Catalog;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $response = $this->getJson(route('v1.categories.index'));

        $response->assertUnauthorized();
    }

    public function test_returns_categories_ordered_by_name(): void
    {
        $camisetas = Category::factory()->create([
            'nome' => 'Camisetas',
        ]);
        $acessorios = Category::factory()->create([
            'nome' => 'Acessórios',
        ]);

        $response = $this->withToken($this->token())->getJson(route('v1.categories.index'));

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $acessorios->id);
        $response->assertJsonPath('data.0.nome', 'Acessórios');
        $response->assertJsonPath('data.1.id', $camisetas->id);
        $response->assertJsonPath('data.1.nome', 'Camisetas');
    }

    private function token(): string
    {
        return User::factory()->create()->createToken('api')->plainTextToken;
    }
}
