<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentUserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $response = $this->get(route('v1.auth.me'));

        $response->assertUnauthorized();
        $response->assertExactJson([
            'message' => 'Não autenticado.',
        ]);
    }

    public function test_returns_401_when_the_token_was_revoked(): void
    {
        $user = User::factory()->create();
        $accessToken = $user->createToken('api');
        $plainTextToken = $accessToken->plainTextToken;
        $accessToken->accessToken->delete();

        $response = $this->withToken($plainTextToken)->getJson(route('v1.auth.me'));

        $response->assertUnauthorized();
        $response->assertExactJson([
            'message' => 'Não autenticado.',
        ]);
    }

    public function test_returns_the_authenticated_user(): void
    {
        $this->travelTo('2026-09-24 12:00:00');

        $user = User::factory()->create([
            'name' => 'Ana Lima',
            'email' => 'ana@example.com',
        ]);
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->getJson(route('v1.auth.me'));

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => $user->id,
                'name' => 'Ana Lima',
                'email' => 'ana@example.com',
                'created_at' => '2026-09-24T12:00:00.000000Z',
                'updated_at' => '2026-09-24T12:00:00.000000Z',
            ],
        ]);
    }
}
