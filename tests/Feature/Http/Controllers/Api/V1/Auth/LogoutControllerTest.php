<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $response = $this->postJson(route('v1.auth.logout'));

        $response->assertUnauthorized();
        $response->assertExactJson([
            'message' => 'Não autenticado.',
        ]);
    }

    public function test_revokes_the_current_token_and_returns_204(): void
    {
        $user = User::factory()->create();
        $accessToken = $user->createToken('api');

        $response = $this->withToken($accessToken->plainTextToken)
            ->postJson(route('v1.auth.logout'));

        $response->assertNoContent();
        $this->assertModelMissing($accessToken->accessToken);
    }
}
