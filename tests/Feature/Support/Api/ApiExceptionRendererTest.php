<?php

namespace Tests\Feature\Support\Api;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

class ApiExceptionRendererTest extends TestCase
{
    public function test_returns_404_when_api_route_does_not_exist(): void
    {
        $response = $this->get('/api/v1/recurso-inexistente');

        $response->assertNotFound();
        $response->assertExactJson([
            'message' => 'Recurso não encontrado.',
        ]);
    }

    public function test_returns_405_when_method_is_not_allowed(): void
    {
        $response = $this->post('/api/v1/health');

        $response->assertMethodNotAllowed();
        $response->assertExactJson([
            'message' => 'Método não permitido.',
        ]);
    }

    public function test_returns_401_when_request_is_unauthenticated(): void
    {
        Route::get('/api/v1/protegido', function (): void {
            throw new AuthenticationException;
        });

        $response = $this->get('/api/v1/protegido');

        $response->assertUnauthorized();
        $response->assertExactJson([
            'message' => 'Não autenticado.',
        ]);
    }

    public function test_returns_403_when_access_is_denied(): void
    {
        Route::get('/api/v1/restrito', function (): void {
            abort(403);
        });

        $response = $this->get('/api/v1/restrito');

        $response->assertForbidden();
        $response->assertExactJson([
            'message' => 'Acesso negado.',
        ]);
    }

    public function test_returns_422_when_payload_is_invalid(): void
    {
        Route::post('/api/v1/probe', function () {
            request()->validate([
                'nome' => ['required', 'string'],
            ]);
        });

        $response = $this->postJson('/api/v1/probe', []);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'Os dados enviados são inválidos.');
        $response->assertJsonValidationErrors(['nome']);
    }

    public function test_returns_429_when_rate_limit_is_exceeded(): void
    {
        Route::get('/api/v1/limite', function (): void {
            throw new TooManyRequestsHttpException(30);
        });

        $response = $this->get('/api/v1/limite');

        $response->assertTooManyRequests();
        $response->assertHeader('Retry-After', '30');
        $response->assertExactJson([
            'message' => 'Muitas tentativas. Tente novamente em instantes.',
        ]);
    }

    public function test_returns_500_without_internal_details_when_debug_is_off(): void
    {
        config(['app.debug' => false]);

        Route::get('/api/v1/falha', function (): void {
            throw new RuntimeException('detalhe interno');
        });

        $response = $this->get('/api/v1/falha');

        $response->assertInternalServerError();
        $response->assertExactJson([
            'message' => 'Erro interno do servidor.',
        ]);
    }

    public function test_returns_500_with_debug_details_when_debug_is_on(): void
    {
        config(['app.debug' => true]);

        Route::get('/api/v1/falha', function (): void {
            throw new RuntimeException('detalhe interno');
        });

        $response = $this->get('/api/v1/falha');

        $response->assertInternalServerError();
        $response->assertJsonPath('message', 'Erro interno do servidor.');
        $response->assertJsonPath('debug.exception', RuntimeException::class);
        $response->assertJsonPath('debug.message', 'detalhe interno');
    }

    public function test_web_not_found_keeps_an_html_response(): void
    {
        $response = $this->get('/pagina-inexistente');

        $response->assertNotFound();
        $this->assertStringContainsString(
            'text/html',
            (string) $response->headers->get('Content-Type'),
        );
    }
}
