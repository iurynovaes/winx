<?php

namespace App\Domain\Auth\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvalidCredentialsException extends Exception implements ShouldntReport
{
    /**
     * Render a credential failure without revealing which field was wrong.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('api.invalid_credentials'),
        ], 401);
    }
}
