<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginUser
{
    /**
     * Issue a personal access token when the credentials match a user.
     *
     * @return array{user: User, token: string}
     */
    public function handle(string $email, string $password): array
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw new InvalidCredentialsException;
        }

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }
}
