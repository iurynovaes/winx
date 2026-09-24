<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Catalog;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $category = Category::factory()->create([
            'nome' => 'Camisetas',
        ]);

        $response = $this->patchJson(route('v1.categories.update', $category), [
            'nome' => 'Roupas',
        ]);

        $response->assertUnauthorized();
        $this->assertSame('Camisetas', $category->fresh()->nome);
    }

    public function test_renames_the_category(): void
    {
        $category = Category::factory()->create([
            'nome' => 'Camisetas',
        ]);

        $response = $this->withToken($this->token())->patchJson(route('v1.categories.update', $category), [
            'nome' => 'Roupas',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.nome', 'Roupas');
        $this->assertSame('Roupas', $category->fresh()->nome);
    }

    public function test_returns_422_when_another_category_has_the_same_name(): void
    {
        Category::factory()->create([
            'nome' => 'Calçados',
        ]);
        $category = Category::factory()->create([
            'nome' => 'Camisetas',
        ]);

        $response = $this->withToken($this->token())->patchJson(route('v1.categories.update', $category), [
            'nome' => 'calçados',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.nome.0', 'Esta categoria já está em uso.');
        $this->assertSame('Camisetas', $category->fresh()->nome);
    }

    public function test_allows_keeping_the_current_name(): void
    {
        $category = Category::factory()->create([
            'nome' => 'Camisetas',
        ]);

        $response = $this->withToken($this->token())->patchJson(route('v1.categories.update', $category), [
            'nome' => 'camisetas',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.nome', 'camisetas');
    }

    private function token(): string
    {
        return User::factory()->create()->createToken('api')->plainTextToken;
    }
}
