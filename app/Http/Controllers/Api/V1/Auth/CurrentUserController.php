<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;

class CurrentUserController extends Controller
{
    /**
     * Return the user authenticated by the bearer token.
     */
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
