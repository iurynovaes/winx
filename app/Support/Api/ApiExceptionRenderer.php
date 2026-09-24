<?php

namespace App\Support\Api;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ApiExceptionRenderer
{
    /**
     * Register the JSON error contract used by every /api route.
     */
    public function register(Exceptions $exceptions): void
    {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => __('api.invalid'),
                'errors' => $exception->errors(),
            ], $exception->status);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return $this->errorResponse(__('api.unauthenticated'), 401);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return $this->errorResponse(
                $this->messageForStatus($exception->getStatusCode()),
                $exception->getStatusCode(),
                $exception->getHeaders(),
            );
        });

        $exceptions->render(function (Throwable $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            $payload = [
                'message' => __('api.server_error'),
            ];

            if (config('app.debug')) {
                $payload['debug'] = [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ];
            }

            return response()->json($payload, 500);
        });
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function errorResponse(string $message, int $status, array $headers = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], $status, $headers);
    }

    private function messageForStatus(int $status): string
    {
        return match ($status) {
            401 => __('api.unauthenticated'),
            403 => __('api.forbidden'),
            404 => __('api.not_found'),
            405 => __('api.method_not_allowed'),
            409 => __('api.conflict'),
            422 => __('api.invalid'),
            429 => __('api.too_many_requests'),
            default => $status >= 500
                ? __('api.server_error')
                : __('api.bad_request'),
        };
    }
}
