<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterUserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_payload_creates_user_and_returns_201(): void
    {
        $this->travelTo('2026-09-24 12:00:00');

        $response = $this->postJson(route('v1.auth.register'), [
            'name' => 'Ana Lima',
            'email' => 'Ana@Example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'remember_token' => 'hacked',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.token_type', 'Bearer');
        $response->assertJsonPath('data.user.name', 'Ana Lima');
        $response->assertJsonPath('data.user.email', 'ana@example.com');
        $response->assertJsonPath('data.user.created_at', '2026-09-24T12:00:00.000000Z');
        $response->assertJsonPath('data.user.updated_at', '2026-09-24T12:00:00.000000Z');
        $this->assertIsString($response->json('data.token'));
        $this->assertArrayNotHasKey('password', $response->json('data.user'));

        $user = User::query()->where('email', 'ana@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertNull($user->remember_token);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_returns_422_when_payload_is_empty(): void
    {
        $response = $this->postJson(route('v1.auth.register'), []);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'Os dados enviados são inválidos.');
        $response->assertJsonPath('errors.name.0', 'Informe o nome.');
        $response->assertJsonPath('errors.email.0', 'Informe o e-mail.');
        $response->assertJsonPath('errors.password.0', 'Informe a senha.');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_returns_422_when_email_is_invalid(): void
    {
        $response = $this->postJson(route('v1.auth.register'), $this->payload([
            'email' => 'ana',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.email.0', 'Informe um e-mail válido.');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_returns_422_when_email_is_already_used(): void
    {
        User::factory()->create([
            'email' => 'ana@example.com',
        ]);

        $response = $this->postJson(route('v1.auth.register'), $this->payload());

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.email.0', 'Este e-mail já está em uso.');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_returns_422_when_name_is_too_long(): void
    {
        $response = $this->postJson(route('v1.auth.register'), $this->payload([
            'name' => str_repeat('a', 256),
        ]));

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.name.0', 'O nome deve ter no máximo 255 caracteres.');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_returns_422_when_password_is_too_short(): void
    {
        $response = $this->postJson(route('v1.auth.register'), $this->payload([
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.password.0', 'A senha deve ter pelo menos 8 caracteres.');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_returns_422_when_password_confirmation_does_not_match(): void
    {
        $response = $this->postJson(route('v1.auth.register'), $this->payload([
            'password_confirmation' => 'other-password',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.password.0', 'A confirmação da senha não confere.');
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ana Lima',
            'email' => 'ana@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }
}
