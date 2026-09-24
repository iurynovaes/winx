<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Catalog;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $response = $this->postJson(route('v1.categories.store'), [
            'nome' => 'Camisetas',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_valid_payload_creates_category_and_returns_201(): void
    {
        $this->travelTo('2026-09-24 12:00:00');

        $response = $this->withToken($this->token())->postJson(route('v1.categories.store'), [
            'nome' => 'Camisetas',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.nome', 'Camisetas');
        $response->assertJsonPath('data.created_at', '2026-09-24T12:00:00.000000Z');
        $this->assertDatabaseHas('categories', [
            'nome' => 'Camisetas',
        ]);
    }

    public function test_returns_422_when_name_is_missing(): void
    {
        $response = $this->withToken($this->token())->postJson(route('v1.categories.store'), []);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.nome.0', 'Informe o nome.');
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_returns_422_when_name_differs_only_by_case(): void
    {
        Category::factory()->create([
            'nome' => 'Camisetas',
        ]);

        $response = $this->withToken($this->token())->postJson(route('v1.categories.store'), [
            'nome' => 'camisetas',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.nome.0', 'Esta categoria já está em uso.');
        $this->assertDatabaseCount('categories', 1);
    }

    private function token(): string
    {
        return User::factory()->create()->createToken('api')->plainTextToken;
    }
}
