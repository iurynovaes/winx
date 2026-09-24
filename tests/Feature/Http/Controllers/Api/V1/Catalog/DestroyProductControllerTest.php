<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Catalog;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson(route('v1.products.destroy', $product));

        $response->assertUnauthorized();
        $response->assertExactJson([
            'message' => 'Não autenticado.',
        ]);
        $this->assertModelExists($product);
    }

    public function test_deletes_the_product_and_returns_204(): void
    {
        $product = Product::factory()->create();

        $response = $this->withToken($this->token())->deleteJson(route('v1.products.destroy', $product));

        $response->assertNoContent();
        $this->assertModelMissing($product);

        $this->withToken($this->token())
            ->getJson(route('v1.products.show', $product))
            ->assertNotFound();
    }

    public function test_returns_404_when_product_does_not_exist(): void
    {
        $response = $this->withToken($this->token())->deleteJson(route('v1.products.destroy', ['product' => 999999]));

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
