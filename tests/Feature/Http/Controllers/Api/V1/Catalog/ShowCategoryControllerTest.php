<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Catalog;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson(route('v1.categories.show', $category));

        $response->assertUnauthorized();
    }

    public function test_returns_the_category(): void
    {
        $this->travelTo('2026-09-24 12:00:00');

        $category = Category::factory()->create([
            'nome' => 'Camisetas',
        ]);

        $response = $this->withToken($this->token())->getJson(route('v1.categories.show', $category));

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => $category->id,
                'nome' => 'Camisetas',
                'created_at' => '2026-09-24T12:00:00.000000Z',
                'updated_at' => '2026-09-24T12:00:00.000000Z',
            ],
        ]);
    }

    public function test_returns_404_when_category_does_not_exist(): void
    {
        $response = $this->withToken($this->token())->getJson(route('v1.categories.show', ['category' => 999999]));

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
