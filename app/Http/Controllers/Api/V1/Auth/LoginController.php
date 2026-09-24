<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\LoginUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\Api\V1\AuthTokenResource;

class LoginController extends Controller
{
    /**
     * Exchange valid credentials for a bearer token.
     */
    public function __invoke(LoginRequest $request, LoginUser $login): AuthTokenResource
    {
        $result = $login->handle(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        return new AuthTokenResource($result);
    }
}
