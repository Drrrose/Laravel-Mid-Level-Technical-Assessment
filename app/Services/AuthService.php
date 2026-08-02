<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Register a new user and generate access token.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string, token_type: string}
     *
     * @throws ValidationException
     * @throws RuntimeException
     */
    public function register(array $data): array
    {
        try {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        try {
            $token = $user->createToken('auth_token')->plainTextToken;
        } catch (\Throwable $e) {
            throw new RuntimeException('Failed to generate authentication token.', 0, $e);
        }

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Authenticate a user and generate access token.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string, token_type: string}
     *
     * @throws AuthenticationException
     * @throws RuntimeException
     */
    public function login(array $data): array
    {
        $user = $this->userRepository->findByEmail($data['email']);

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        try {
            $token = $user->createToken('auth_token')->plainTextToken;
        } catch (\Throwable $e) {
            throw new RuntimeException('Failed to generate authentication token.', 0, $e);
        }

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Logout user by deleting current token.
     *
     * @throws AuthenticationException
     */
    public function logout(?User $user): void
    {
        $token = $user?->currentAccessToken();

        if (! $user || ! $token) {
            throw new AuthenticationException('Unauthenticated or invalid token.');
        }

        $token->delete();
    }
}
