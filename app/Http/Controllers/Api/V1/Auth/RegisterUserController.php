<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Resources\Api\V1\AuthTokenResource;
use Illuminate\Http\JsonResponse;

class RegisterUserController extends Controller
{
    /**
     * Register a user and return a bearer token.
     */
    public function __invoke(RegisterUserRequest $request, RegisterUser $register): JsonResponse
    {
        $result = $register->handle($request->safe()->only(['name', 'email', 'password']));

        return (new AuthTokenResource($result))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }
}
