<?php

namespace App\Domain\Auth\Actions;

use App\Models\User;

class RegisterUser
{
    /**
     * Create a user and issue a personal access token.
     *
     * @param  array{name: string, email: string, password: string}  $attributes
     * @return array{user: User, token: string}
     */
    public function handle(array $attributes): array
    {
        $user = User::query()->create([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
        ]);

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }
}
