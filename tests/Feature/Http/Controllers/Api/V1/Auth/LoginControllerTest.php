<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_return_a_bearer_token(): void
    {
        User::factory()->create([
            'name' => 'Ana Lima',
            'email' => 'ana@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson(route('v1.auth.login'), [
            'email' => 'Ana@Example.com',
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.token_type', 'Bearer');
        $response->assertJsonPath('data.user.email', 'ana@example.com');
        $response->assertJsonPath('data.user.name', 'Ana Lima');
        $this->assertIsString($response->json('data.token'));
        $this->assertArrayNotHasKey('password', $response->json('data.user'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    #[DataProvider('invalidCredentials')]
    public function test_returns_401_when_credentials_are_invalid(string $email, string $password): void
    {
        User::factory()->create([
            'email' => 'ana@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson(route('v1.auth.login'), [
            'email' => $email,
            'password' => $password,
        ]);

        $response->assertUnauthorized();
        $response->assertExactJson([
            'message' => 'Credenciais inválidas.',
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_returns_422_when_payload_is_empty(): void
    {
        $response = $this->postJson(route('v1.auth.login'), []);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'Os dados enviados são inválidos.');
        $response->assertJsonPath('errors.email.0', 'Informe o e-mail.');
        $response->assertJsonPath('errors.password.0', 'Informe a senha.');
    }

    public function test_returns_422_when_email_is_invalid(): void
    {
        $response = $this->postJson(route('v1.auth.login'), [
            'email' => 'ana',
            'password' => 'password',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.email.0', 'Informe um e-mail válido.');
    }

    public function test_returns_429_after_five_failed_attempts_for_the_same_email_and_ip(): void
    {
        User::factory()->create([
            'email' => 'ana@example.com',
            'password' => 'password',
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->postJson(route('v1.auth.login'), [
                'email' => 'ana@example.com',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $response = $this->postJson(route('v1.auth.login'), [
            'email' => 'ana@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertTooManyRequests();
        $response->assertExactJson([
            'message' => 'Muitas tentativas. Tente novamente em instantes.',
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidCredentials(): array
    {
        return [
            'wrong password' => ['ana@example.com', 'wrong-password'],
            'unknown email' => ['outra@example.com', 'password'],
        ];
    }
}
